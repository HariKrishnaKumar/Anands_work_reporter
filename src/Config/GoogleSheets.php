<?php
declare(strict_types=1);

namespace App\Config;

class GoogleSheets
{
    public static function isEnabled(): bool
    {
        return ($_ENV['GOOGLE_SHEETS_ENABLED'] ?? 'false') === 'true';
    }

    public static function getCredentialsPath(): string
    {
        $relative = $_ENV['GOOGLE_SHEETS_CREDENTIALS_PATH'] ?? 'storage/google-credentials.json';
        return dirname(__DIR__, 2) . '/' . ltrim($relative, '/');
    }

    public static function getSpreadsheetId(): string
    {
        return $_ENV['GOOGLE_SHEETS_SPREADSHEET_ID'] ?? '';
    }

    public static function getWorksheet(): string
    {
        return $_ENV['GOOGLE_SHEETS_WORKSHEET'] ?? 'Work Reports';
    }
}
