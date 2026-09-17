<?php
declare(strict_types=1);

namespace App\Config;

use Dotenv\Dotenv;

class Database
{
    private static ?\PDO $instance = null;

    public static function loadEnv(): void
    {
        $rootDir = dirname(__DIR__, 2);
        if (file_exists($rootDir . '/.env')) {
            $dotenv = Dotenv::createImmutable($rootDir);
            $dotenv->load();
        }
    }

    public static function getConnection(): \PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $database = $_ENV['DB_DATABASE'] ?? 'daily_work_report';
            $username = $_ENV['DB_USERNAME'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // Aiven SSL/TLS support
            $sslCa = $_ENV['DB_SSL_CA'] ?? '';
            $sslVerify = ($_ENV['DB_SSL_VERIFY'] ?? 'true') === 'true';

            if (!empty($sslCa) && file_exists($sslCa)) {
                $options[\PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
                if ($sslVerify) {
                    $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
                }
            }

            self::$instance = new \PDO($dsn, $username, $password, $options);
        }

        return self::$instance;
    }
}
