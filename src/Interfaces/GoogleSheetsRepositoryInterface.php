<?php
declare(strict_types=1);

namespace App\Interfaces;

interface GoogleSheetsRepositoryInterface
{
    /**
     * Find the row number (1-based) in the sheet where column A matches the given Report ID.
     * Returns 0 if not found.
     */
    public function findRowByReportId(int $reportId): int;

    /**
     * Append a new row to the sheet.
     */
    public function appendRow(array $values): void;

    /**
     * Update an existing row (1-based row number).
     */
    public function updateRow(int $rowNumber, array $values): void;

    /**
     * Get all values from column A (Report IDs) for deduplication.
     * @return array<int, int> rowNumber => reportId
     */
    public function getAllReportIds(): array;
}
