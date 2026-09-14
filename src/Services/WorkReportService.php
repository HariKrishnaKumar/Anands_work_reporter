<?php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\WorkReportRepositoryInterface;

class WorkReportService
{
    private WorkReportRepositoryInterface $reportRepo;
    private FileStorageService $fileStorageService;
    private ?GoogleSheetsSyncService $sheetsSyncService = null;

    public function __construct(WorkReportRepositoryInterface $reportRepo, FileStorageService $fileStorageService, ?GoogleSheetsSyncService $sheetsSyncService = null)
    {
        $this->reportRepo = $reportRepo;
        $this->fileStorageService = $fileStorageService;
        $this->sheetsSyncService = $sheetsSyncService;
    }

    public function validate(array $data): array
    {
        $errors = [];

        $workDate = $data['work_date'] ?? '';
        $description = $data['description'] ?? '';

        if (empty($workDate)) {
            $errors[] = 'Work date is required.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $workDate)) {
            $errors[] = 'Invalid work date format.';
        } else {
            $dt = \DateTime::createFromFormat('Y-m-d', $workDate);
            if (!$dt || $dt->format('Y-m-d') !== $workDate) {
                $errors[] = 'Invalid work date.';
            }
        }

        $description = trim($description);
        if (empty($description)) {
            $errors[] = 'Work description is required.';
        } elseif (strlen($description) < 10) {
            $errors[] = 'Work description must be at least 10 characters.';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Save a work report with optional files (BLOB storage).
     * $files is the reconstructed $_FILES-shaped array from session.
     */
    public function saveReport(int $userId, string $workDate, string $description, array $files = []): array
    {
        $report = $this->reportRepo->create($userId, $workDate, $description);
        $reportId = (int)$report['id'];

        // Files arrive as staged temp paths from preview()
        $stagedPaths = $_SESSION['staged_files'] ?? [];
        error_log("[SAVE] staged=" . count($stagedPaths) . " session_keys=" . implode(',', array_keys($_SESSION)));

        if (!empty($stagedPaths)) {
            $this->fileStorageService->storeStagedFiles($stagedPaths, $reportId, $userId);
            unset($_SESSION['staged_files']);
        } elseif (!empty($files) && !empty($files['name'][0]) && $files['name'][0] !== '') {
            // Fallback: direct same-request upload (legacy path)
            $validation = $this->fileStorageService->validateFiles($files);
            if (!empty($validation['valid'])) {
                $this->fileStorageService->storeFiles($validation['valid'], $reportId, $userId);
            }
        }

        $report['files'] = $this->reportRepo->getFilesByReport($reportId, $userId);

        // Sync to Google Sheets (non-blocking — MariaDB is primary)
        if ($this->sheetsSyncService !== null) {
            error_log("[SHEETS] Attempting sync: report_id={$reportId}");
            $this->sheetsSyncService->syncReport($report, $userId);
        }

        return $report;
    }

    public function getReport(int $reportId, int $userId): ?array
    {
        $report = $this->reportRepo->findByIdAndUser($reportId, $userId);
        if ($report) {
            $report['files'] = $this->reportRepo->getFilesByReport($reportId, $userId);
        }
        return $report;
    }

    public function getUserReports(int $userId): array
    {
        $reports = $this->reportRepo->findAllByUser($userId);
        foreach ($reports as &$report) {
            $report['files'] = $this->reportRepo->getFilesByReport((int)$report['id'], $userId);
        }
        return $reports;
    }

    public function getFile(int $fileId, int $userId): ?array
    {
        return $this->reportRepo->getFileById($fileId, $userId);
    }
}
