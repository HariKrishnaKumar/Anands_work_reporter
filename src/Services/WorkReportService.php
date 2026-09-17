<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\WorkReportRepository;

class WorkReportService
{
    private WorkReportRepository $reportRepo;
    private FileStorageService $fileStorageService;
    private ?GoogleSheetsSyncService $sheetsSyncService = null;

    public function __construct(WorkReportRepository $reportRepo, FileStorageService $fileStorageService, ?GoogleSheetsSyncService $sheetsSyncService = null)
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
     *
     * @param array $stagedPaths Array of staged temp file paths from preview step
     */
    public function saveReport(int $userId, string $workDate, string $description, array $stagedPaths = []): array
    {
        $report = $this->reportRepo->create($userId, $workDate, $description);
        $reportId = (int)$report['id'];

        if (!empty($stagedPaths)) {
            $this->fileStorageService->storeStagedFiles($stagedPaths, $reportId, $userId);
        }

        $report['files'] = $this->reportRepo->getFilesByReport($reportId, $userId);

        // Sync to Google Sheets (non-blocking — MariaDB is primary)
        if ($this->sheetsSyncService !== null) {
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

    public function searchUserReports(int $userId, string $query = '', string $date = ''): array
    {
        $reports = $this->reportRepo->searchByUser($userId, $query, $date);
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