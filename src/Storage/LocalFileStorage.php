<?php
declare(strict_types=1);

namespace App\Storage;

use App\Interfaces\FileStorageInterface;

class LocalFileStorage implements FileStorageInterface
{
    private string $uploadDir;

    public function __construct(?string $uploadDir = null)
    {
        $this->uploadDir = $uploadDir ?? dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function store(array $file, int $userId): array
    {
        $userDir = $this->uploadDir . '/' . $userId;
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }

        $originalFilename = $file['name'];
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $storedFilename = bin2hex(random_bytes(16)) . '.' . $extension;
        $storagePath = $userId . '/' . $storedFilename;
        $fullPath = $this->uploadDir . '/' . $storagePath;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        return [
            'stored_filename' => $storedFilename,
            'storage_path' => $storagePath,
        ];
    }

    public function getFullPath(string $storagePath): string
    {
        return $this->uploadDir . '/' . ltrim($storagePath, '/');
    }

    public function exists(string $storagePath): bool
    {
        return file_exists($this->getFullPath($storagePath));
    }

    public function delete(string $storagePath): bool
    {
        $fullPath = $this->getFullPath($storagePath);
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public function getMimeType(string $storagePath): string
    {
        $fullPath = $this->getFullPath($storagePath);
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $fullPath);
            finfo_close($finfo);
            return $mime ?: 'application/octet-stream';
        }
        return mime_content_type($fullPath) ?: 'application/octet-stream';
    }
}
