<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\WorkReportRepository;

class FileStorageService
{
    private WorkReportRepository $reportRepo;

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_TOTAL_SIZE = 30 * 1024 * 1024; // 30MB
    private const BLOCKED_EXTENSIONS = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat', 'cmd', 'com', 'msi', 'scr', 'js', 'vbs', 'wsf'];

    /** @var string Temp directory for staging files between requests */
    private string $tempDir;

    public function __construct(WorkReportRepository $reportRepo)
    {
        $this->reportRepo = $reportRepo;
        $this->tempDir = dirname(__DIR__, 2) . '/storage/temp';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Validate an array of uploaded files
     */
    public function validateFiles(array $files): array
    {
        $valid = [];
        $errors = [];
        $totalSize = 0;

        $fileCount = is_array($files['name']) ? count($files['name']) : ($files['name'] ? 1 : 0);

        for ($i = 0; $i < $fileCount; $i++) {
            $name = $files['name'][$i] ?? $files['name'];
            $tmpName = $files['tmp_name'][$i] ?? $files['tmp_name'];
            $size = (int)($files['size'][$i] ?? $files['size']);
            $error = $files['error'][$i] ?? $files['error'];

            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = "Upload error for {$name}: code {$error}";
                continue;
            }

            // If name is empty (e.g. programmatic upload), derive from MIME type
            if (empty($name)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                $extMap = [
                    'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif',
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                ];
                $extension = $extMap[$mimeType] ?? 'bin';
                $name = 'upload_' . ($i + 1) . '.' . $extension;
            } else {
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            }
            if (in_array($extension, self::BLOCKED_EXTENSIONS)) {
                $errors[] = "File type not allowed: {$extension}";
                continue;
            }
            if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
                $errors[] = "Unsupported file type: {$extension}";
                continue;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpName);
            finfo_close($finfo);

            if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
                $errors[] = "File content type not allowed for: {$name}";
                continue;
            }

            if ($size > self::MAX_FILE_SIZE) {
                $errors[] = "File too large: {$name} (max 10MB)";
                continue;
            }

            $totalSize += $size;
            if ($totalSize > self::MAX_TOTAL_SIZE) {
                $errors[] = "Total file size exceeds 30MB limit";
                continue;
            }

            $valid[] = [
                'name' => $name,
                'tmp_name' => $tmpName,
                'size' => $size,
                'mime_type' => $mimeType,
                'extension' => $extension,
            ];
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /**
     * Move uploaded files from PHP tmp to our staging dir so they persist across requests.
     * Returns an array of staged file paths keyed by index.
     */
    public function stageFiles(array $validFiles): array
    {
        $sessionId = session_id();
        $staged = [];
        foreach ($validFiles as $i => $file) {
            $dest = $this->tempDir . '/' . $sessionId . '_' . $i . '_' . bin2hex(random_bytes(8));
            if (!is_dir($this->tempDir)) {
                mkdir($this->tempDir, 0755, true);
            }
            $ok = @move_uploaded_file($file['tmp_name'], $dest);
            error_log("[STAGE] $i ok=$ok tmp={$file['tmp_name']} dest=$dest");
            if ($ok) {
                $staged[] = $dest;
            }
        }
        return $staged;
    }

    /**
     * Read staged file bytes and store as BLOB in the database.
     */
    public function storeStagedFiles(array $stagedPaths, int $reportId, int $userId): void
    {
        error_log("[STORE] paths=" . count($stagedPaths));
        foreach ($stagedPaths as $path) {
            if (!file_exists($path)) {
                error_log("[STORE] MISSING: $path");
                continue;
            }

            $fileData = file_get_contents($path);
            $fileSize = strlen($fileData);
            $mimeType = mime_content_type($path) ?: 'application/octet-stream';
            $originalFilename = basename($path);

            $this->reportRepo->addFileWithData(
                $reportId,
                $originalFilename,
                $mimeType,
                $fileSize,
                $fileData
            );

            unlink($path);
        }
    }

    /**
     * Legacy method — stores files directly. Used when files arrive fresh (same request).
     */
    public function storeFiles(array $validatedFiles, int $reportId, int $userId): array
    {
        foreach ($validatedFiles as $file) {
            $fileData = file_get_contents($file['tmp_name']);
            $fileSize = strlen($fileData);

            $this->reportRepo->addFileWithData(
                $reportId,
                $file['name'],
                $file['mime_type'],
                $fileSize,
                $fileData
            );
        }
        return [];
    }

    /**
     * Clean up stale temp files (older than 1 hour)
     */
    public function cleanupTemp(): void
    {
        $files = glob($this->tempDir . '/*');
        if (!$files) return;
        $cutoff = time() - 3600;
        foreach ($files as $f) {
            if (is_file($f) && filemtime($f) < $cutoff) {
                unlink($f);
            }
        }
    }
}
