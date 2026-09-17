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
                $sslCaPath = self::resolveSslCa($sslCa);

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

    /**
     * Resolve SSL CA certificate to a file path.
     * Handles: file path, PEM text, base64-encoded DER.
     * Returns path to a PEM file, or null on failure.
     */
    private static function resolveSslCa(string $sslCa): ?string
    {
        // 1. If it's a file path that exists, use it directly
        if (file_exists($sslCa)) {
            return $sslCa;
        }

        $tempDir = sys_get_temp_dir();
        $hash = md5($sslCa);

        // 2. If it starts with "-----BEGIN", it's PEM text
        if (str_starts_with(trim($sslCa), "-----BEGIN CERTIFICATE-----")) {
            $pemFile = "$tempDir/aiven-ca-$hash.pem";
            file_put_contents($pemFile, $sslCa);
            if (filesize($pemFile) > 0) {
                return $pemFile;
            }
        }

        // 3. Try base64 decode → could be DER binary
        $decoded = base64_decode($sslCa, true);
        if ($decoded === false || strlen($decoded) < 50) {
            return null;
        }

        // Check if decoded content is actually PEM text (base64-encoded PEM)
        $decodedText = trim($decoded);
        if (str_starts_with($decodedText, "-----BEGIN CERTIFICATE-----")) {
            $pemFile = "$tempDir/aiven-ca-$hash.pem";
            file_put_contents($pemFile, $decodedText);
            if (filesize($pemFile) > 0) {
                return $pemFile;
            }
        }

        // It's DER binary — convert to PEM using PHP's openssl functions
        $derFile = "$tempDir/aiven-ca-$hash.der";
        file_put_contents($derFile, $decoded);

        // Try reading as DER certificate
        $cert = @openssl_x509_read($derFile);
        if ($cert !== false) {
            $pemFile = "$tempDir/aiven-ca-$hash.pem";
            if (@openssl_x509_export($cert, $pemContent) && !empty($pemContent)) {
                file_put_contents($pemFile, $pemContent);
                @openssl_x509_free($cert);
                @unlink($derFile);
                return $pemFile;
            }
            @openssl_x509_free($cert);
        }

        // Fallback: try reading the DER file directly as a certificate resource
        // Some PHP builds accept raw DER via MYSQL_ATTR_SSL_CA
        @unlink($derFile);
        return null;
    }
}
