<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\GoogleSheets;
use App\Repositories\GoogleSheetsRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkReportRepository;

class GoogleSheetsSyncService
{
    private GoogleSheetsRepository $sheetsRepo;
    private UserRepository $userRepo;
    private WorkReportRepository $reportRepo;

    public function __construct(GoogleSheetsRepository $sheetsRepo, UserRepository $userRepo, WorkReportRepository $reportRepo)
    {
        $this->sheetsRepo = $sheetsRepo;
        $this->userRepo = $userRepo;
        $this->reportRepo = $reportRepo;
    }

    /**
     * Sync a single report to Google Sheets.
     * Uses Report ID as unique key — updates existing row or appends new.
     * Never throws on failure — logs and returns false.
     */
    public function syncReport(array $report, int $userId): bool
    {
        if (!GoogleSheets::isEnabled()) {
            return false;
        }

        try {
            $user = $this->userRepo->findById($userId);
            $userName = $user['display_name'] ?? 'Unknown';
            $userEmail = $user['email'] ?? '';

            $attachmentCount = count($report['files'] ?? []);
            $attachmentNames = '';
            if ($attachmentCount > 0) {
                $names = array_column($report['files'], 'original_filename');
                $attachmentNames = implode(', ', $names);
            }

            $row = [
                (int)$report['id'],
                $userId,
                $userName,
                $userEmail,
                $report['work_date'] ?? '',
                $report['description'] ?? '',
                $attachmentCount,
                $attachmentNames,
                $report['created_at'] ?? date('Y-m-d H:i:s'),
            ];

            $existingRow = $this->sheetsRepo->findRowByReportId((int)$report['id']);

            if ($existingRow > 0) {
                $this->sheetsRepo->updateRow($existingRow, $row);
            } else {
                $this->sheetsRepo->appendRow($row);
            }

            return true;
        } catch (\Exception $e) {
            error_log("[SHEETS] Sync failed: report_id=" . ($report['id'] ?? '?') . " error=" . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync all reports from MariaDB to Google Sheets (backfill).
     * Idempotent — will not create duplicates.
     *
     * @return array{synced: int, failed: int, skipped: int}
     */
    public function syncAll(): array
    {
        $result = ['synced' => 0, 'failed' => 0, 'skipped' => 0];

        if (!GoogleSheets::isEnabled()) {
            $result['error'] = 'Google Sheets integration is disabled';
            return $result;
        }

        try {
            $existingIds = $this->sheetsRepo->getAllReportIds();
            $existingIdValues = array_values($existingIds);
        } catch (\Exception $e) {
            $result['error'] = 'Failed to read spreadsheet: ' . $e->getMessage();
            return $result;
        }

        $reports = $this->reportRepo->findAll();

        foreach ($reports as $report) {
            $reportId = (int)$report['id'];
            $report['files'] = $this->reportRepo->getFilesByReportId($reportId);

            $row = [
                $reportId,
                (int)$report['user_id'],
                $report['display_name'] ?? 'Unknown',
                $report['email'] ?? '',
                $report['work_date'] ?? '',
                $report['description'] ?? '',
                count($report['files']),
                implode(', ', array_column($report['files'], 'original_filename')),
                $report['created_at'] ?? '',
            ];

            try {
                if (in_array($reportId, $existingIdValues)) {
                    $rowNum = array_search($reportId, $existingIdValues);
                    if ($rowNum !== false) {
                        $this->sheetsRepo->updateRow((int)array_keys($existingIds)[array_search($reportId, $existingIdValues)] ?? $rowNum, $row);
                    }
                } else {
                    $this->sheetsRepo->appendRow($row);
                    $existingIdValues[] = $reportId;
                }
                $result['synced']++;
            } catch (\Exception $e) {
                $result['failed']++;
            }
        }

        return $result;
    }
}