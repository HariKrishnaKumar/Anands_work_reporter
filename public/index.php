<?php
declare(strict_types=1);

session_start();

// Load environment and autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

Database::loadEnv();

// Generate CSRF token if needed
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$router = new \AltoRouter();

// Set base path
$router->setBasePath('/daily-work-report/public');

// --- Auth Routes ---
$router->map('GET', '/login', [\App\Controllers\AuthController::class, 'loginForm'], 'login');
$router->map('POST', '/login', [\App\Controllers\AuthController::class, 'login'], 'login.post');
$router->map('GET', '/logout', [\App\Controllers\AuthController::class, 'logout'], 'logout');

// Dev login route (only works in dev environment)
if (isDevEnvironment()) {
    $router->map('GET', '/dev-login', [\App\Controllers\AuthController::class, 'devLogin'], 'dev-login');
}

// --- Main Routes ---
$router->map('GET', '/', [\App\Controllers\HomeController::class, 'index'], 'home');
$router->map('GET', '/home', [\App\Controllers\HomeController::class, 'index'], 'home.index');

// --- Report Routes ---
$router->map('GET', '/report/add', [\App\Controllers\ReportController::class, 'add'], 'report.add');
$router->map('POST', '/report/preview', [\App\Controllers\ReportController::class, 'preview'], 'report.preview');
$router->map('GET', '/report/review', [\App\Controllers\ReportController::class, 'review'], 'report.review');
$router->map('POST', '/report/save', [\App\Controllers\ReportController::class, 'save'], 'report.save');
$router->map('GET', '/report/success', [\App\Controllers\ReportController::class, 'success'], 'report.success');
$router->map('GET', '/report/[i:id]', [\App\Controllers\ReportController::class, 'view'], 'report.view');

// --- File Routes ---
$router->map('GET', '/files/[i:id]', [\App\Controllers\FileController::class, 'serve'], 'file.serve');

// --- Google Sheets Backfill (dev only) ---
if (isDevEnvironment()) {
    $router->map('GET', '/sheets/sync-all', [\App\Controllers\SheetsController::class, 'syncAll'], 'sheets.sync-all');
}

// --- Match current request ---
$uri = $_SERVER['REQUEST_URI'];
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $router->match($uri, $_SERVER['REQUEST_METHOD']);

if ($routeInfo) {
    $target = $routeInfo['target'];
    $params = $routeInfo['params'];

    if (is_array($target)) {
        $controller = new $target[0]();
        $method = $target[1];
        $controller->$method(...array_values($params));
    } elseif (is_callable($target)) {
        $target(...array_values($params));
    }
} else {
    // 404
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404</title></head><body><h1>Page Not Found</h1><p><a href="' . ($_ENV['APP_URL'] ?? '/') . '">Go Home</a></p></body></html>';
}
