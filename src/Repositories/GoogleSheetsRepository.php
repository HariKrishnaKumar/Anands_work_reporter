<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Config\GoogleSheets;
use App\Interfaces\GoogleSheetsRepositoryInterface;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class GoogleSheetsRepository implements GoogleSheetsRepositoryInterface
{
    private ?Sheets $service = null;

    private function getService(): Sheets
    {
        if ($this->service !== null) {
            return $this->service;
        }

        $credentialsPath = GoogleSheets::getCredentialsPath();
        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException("Google credentials file not found: {$credentialsPath}");
        }

        $client = new Client();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Sheets::SPREADSHEETS);
        $client->setApplicationName('Daily Work Report');

        $this->service = new Sheets($client);
        return $this->service;
    }

    private function spreadsheetId(): string
    {
        return GoogleSheets::getSpreadsheetId();
    }

    private function worksheetRange(string $range = ''): string
    {
        $sheet = GoogleSheets::getWorksheet();
        return $range !== '' ? "{$sheet}!{$range}" : $sheet;
    }

    public function findRowByReportId(int $reportId): int
    {
        $service = $this->getService();
        $range = $this->worksheetRange('A:A');

        $response = $service->spreadsheets_values->get($this->spreadsheetId(), $range);
        $values = $response->getValues() ?? [];

        foreach ($values as $index => $row) {
            if (!empty($row[0]) && (int)$row[0] === $reportId) {
                return $index + 1; // 1-based row number
            }
        }

        return 0;
    }

    public function appendRow(array $values): void
    {
        $service = $this->getService();
        $range = $this->worksheetRange();

        $body = new ValueRange(['values' => [$values]]);
        $service->spreadsheets_values->append(
            $this->spreadsheetId(),
            $range,
            $body,
            ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
        );
    }

    public function updateRow(int $rowNumber, array $values): void
    {
        $service = $this->getService();
        $range = $this->worksheetRange("A{$rowNumber}:I{$rowNumber}");

        $body = new ValueRange(['values' => [$values]]);
        $service->spreadsheets_values->update(
            $this->spreadsheetId(),
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );
    }

    public function getAllReportIds(): array
    {
        $service = $this->getService();
        $range = $this->worksheetRange('A:A');

        $response = $service->spreadsheets_values->get($this->spreadsheetId(), $range);
        $values = $response->getValues() ?? [];

        $result = [];
        foreach ($values as $index => $row) {
            if (!empty($row[0]) && is_numeric($row[0])) {
                $result[$index + 1] = (int)$row[0];
            }
        }

        return $result;
    }
}
