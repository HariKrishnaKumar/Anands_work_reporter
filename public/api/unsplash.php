<?php
declare(strict_types=1);

/**
 * Unsplash API Server-Side Proxy
 * 
 * Browser calls: /api/unsplash.php?count=8&query=nature
 * This script calls: https://api.unsplash.com/photos/random?count=...
 * 
 * The API key is NEVER exposed to the browser.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Config\Database;
use App\Config\Unsplash;

// Load .env so $_ENV['UNSPLASH_ACCESS_KEY'] is available
Database::loadEnv();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300'); // 5 min cache

// Check if Unsplash is configured
if (!Unsplash::isConfigured()) {
    http_response_code(503);
    echo json_encode([
        'error' => 'Unsplash API not configured',
        'images' => []
    ]);
    exit;
}

// Validate and sanitize parameters
$count = isset($_GET['count']) ? (int)$_GET['count'] : 8;
$count = max(1, min($count, 10)); // Clamp 1-10

$query = isset($_GET['query']) ? trim($_GET['query']) : 'nature landscape';
$query = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $query); // Sanitize
$query = urlencode($query);

$orientation = isset($_GET['orientation']) ? trim($_GET['orientation']) : 'landscape';
if (!in_array($orientation, ['landscape', 'portrait', 'squarish'], true)) {
    $orientation = 'landscape';
}

$contentFilter = isset($_GET['content_filter']) ? trim($_GET['content_filter']) : 'high';
if (!in_array($contentFilter, ['low', 'high'], true)) {
    $contentFilter = 'high';
}

// Build Unsplash API URL
$apiKey = Unsplash::key();
$apiUrl = "https://api.unsplash.com/photos/random?count={$count}&query={$query}&orientation={$orientation}&content_filter={$contentFilter}";

// Make the API request
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        "Authorization: Client-ID {$apiKey}",
        "Accept-Version: v1"
    ],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle cURL errors
if ($response === false || $curlError) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Failed to connect to Unsplash API',
        'detail' => $curlError ?: 'Unknown error',
        'images' => []
    ]);
    exit;
}

// Handle HTTP errors from Unsplash
if ($httpCode !== 200) {
    http_response_code($httpCode === 403 ? 429 : 502);
    echo json_encode([
        'error' => 'Unsplash API returned status ' . $httpCode,
        'images' => []
    ]);
    exit;
}

// Parse the response
$data = json_decode($response, true);
if (!$data || !is_array($data)) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Invalid response from Unsplash API',
        'images' => []
    ]);
    exit;
}

// Normalize: Unsplash returns a single object for count=1, array for count>1
$photos = isset($data['urls']) ? [$data] : $data;

// Transform to minimal response
$images = [];
foreach ($photos as $photo) {
    if (!isset($photo['urls']['regular']) || !isset($photo['user'])) {
        continue;
    }
    
    $photographer = $photo['user']['name'] ?? 'Unknown';
    $photographerUsername = $photo['user']['username'] ?? '';
    $photographerProfile = $photo['user']['links']['html'] ?? '';
    
    $images[] = [
        'url' => $photo['urls']['regular'],
        'thumb' => $photo['urls']['small'] ?? $photo['urls']['regular'],
        'photographer' => $photographer,
        'photographerUsername' => $photographerUsername,
        'photographerUrl' => $photographerProfile . '?utm_source=daily_work_report&utm_medium=referral',
        'unsplashUrl' => 'https://unsplash.com/?utm_source=daily_work_report&utm_medium=referral',
        'alt' => $photo['alt_description'] ?? $photo['description'] ?? 'Nature photograph',
        'color' => $photo['color'] ?? '#333333',
    ];
}

echo json_encode(['images' => $images]);
