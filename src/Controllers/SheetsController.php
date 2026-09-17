<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Config\GoogleSheets;
use App\Services\GoogleSheetsSyncService;
use App\Repositories\GoogleSheetsRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkReportRepository;

class SheetsController
{
    public function syncAll(): void
    {
        header('Content-Type: application/json');

        if (!GoogleSheets::isEnabled()) {
            http_response_code(400);
            echo json_encode(['error' => 'Google Sheets integration is disabled. Set GOOGLE_SHEETS_ENABLED=true in .env']);
            return;
        }

        $credentialsPath = GoogleSheets::getCredentialsPath();
        if (!file_exists($credentialsPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Google credentials file not found: ' . basename($credentialsPath)]);
            return;
        }

        $spreadsheetId = GoogleSheets::getSpreadsheetId();
        if (empty($spreadsheetId)) {
            http_response_code(400);
            echo json_encode(['error' => 'GOOGLE_SHEETS_SPREADSHEET_ID is not set in .env']);
            return;
        }

        $sheetsRepo = new GoogleSheetsRepository();
        $userRepo = new UserRepository();
        $reportRepo = new WorkReportRepository();
        $syncService = new GoogleSheetsSyncService($sheetsRepo, $userRepo, $reportRepo);

        $result = $syncService->syncAll();

        error_log("[SHEETS] Backfill complete: synced={$result['synced']} failed={$result['failed']}");

        echo json_encode([
            'status' => 'ok',
            'spreadsheet_id' => $spreadsheetId,
            'worksheet' => GoogleSheets::getWorksheet(),
            'synced' => $result['synced'],
            'failed' => $result['failed'],
        ]);
    }
}