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

            if (!empty($sslCa)) {
                $sslCaPath = null;

                if (file_exists($sslCa)) {
                    // DB_SSL_CA is a file path — use directly
                    $sslCaPath = $sslCa;
                } elseif (function_exists('base64_decode')) {
                    // DB_SSL_CA may be inline base64-encoded certificate (from deployment-secrets.txt)
                    // Decode and write to a temp file for PDO
                    $decoded = base64_decode($sslCa, true);
                    if ($decoded !== false && strlen($decoded) > 50) {
                        $tempCert = sys_get_temp_dir() . '/aiven-ca-' . md5($sslCa) . '.pem';

                        // Check if decoded content is DER (binary) or PEM (text)
                        if (str_starts_with($decoded, "-----BEGIN CERTIFICATE-----")) {
                            // Already PEM text
                            file_put_contents($tempCert, $decoded);
                        } else {
                            // DER binary — write and convert using openssl if available
                            $derFile = $tempCert . '.der';
                            file_put_contents($derFile, $decoded);
                            $output = [];
                            $exitCode = 0;
                            exec("openssl x509 -inform DER -in " . escapeshellarg($derFile) . " -out " . escapeshellarg($tempCert) . " 2>&1", $output, $exitCode);
                            if ($exitCode !== 0 || !file_exists($tempCert)) {
                                // Fallback: use raw DER (PDO may accept it)
                                rename($derFile, $tempCert);
                            } else {
                                @unlink($derFile);
                            }
                        }

                        if (file_exists($tempCert) && filesize($tempCert) > 0) {
                            $sslCaPath = $tempCert;
                        }
                    }
                }

                if ($sslCaPath !== null) {
                    $options[\PDO::MYSQL_ATTR_SSL_CA] = $sslCaPath;
                    if ($sslVerify) {
                        $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
                    }
                }
            }

            self::$instance = new \PDO($dsn, $username, $password, $options);
        }

        return self::$instance;
    }
}
