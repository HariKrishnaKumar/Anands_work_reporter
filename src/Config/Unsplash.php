<?php
declare(strict_types=1);

namespace App\Config;

/**
 * Unsplash API Configuration
 * 
 * Server-side only — NEVER exposed to browser JavaScript.
 * 
 * To get an API key:
 * 1. Go to https://unsplash.com/developers
 * 2. Create an application
 * 3. Copy your "Access Key"
 * 4. Paste it below in the API_KEY constant
 */
class Unsplash
{
    public static function key(): string
    {
        return $_ENV['UNSPLASH_ACCESS_KEY'] ?? '';
    }

    public static function isConfigured(): bool
    {
        $key = self::key();
        return !empty($key) && $key !== 'YOUR_UNSPLASH_ACCESS_KEY_HERE';
    }
}
