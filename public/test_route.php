<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers/helpers.php';

use App\Config\Database;
Database::loadEnv();

$uri = $_SERVER['REQUEST_URI'] ?? '/';
echo "URI: " . $uri . PHP_EOL;

if (false !== $pos = strpos($uri, '?')) { $uri = substr($uri, 0, $pos); }
$uri = rawurldecode($uri);
echo "Clean URI: " . $uri . PHP_EOL;

$router = new \AltoRouter();
$router->setBasePath('/daily-work-report/public');
$router->map('GET', '/login', function() { echo "LOGIN MATCHED!"; }, 'login');
$router->map('GET', '/', function() { echo "HOME MATCHED!"; }, 'home');

$routeInfo = $router->match('GET', $uri);
echo "Route info: ";
var_export($routeInfo);
echo PHP_EOL;
