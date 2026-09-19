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
     * Resolve SSL CA certificate to a valid PEM file path.
     * Handles: PEM file, DER file, PEM text, base64-encoded DER.
     * Returns path to a PEM file, or null on failure.
     */
    private static function resolveSslCa(string $sslCa): ?string
    {
        $tempDir = sys_get_temp_dir();
        $hash = md5($sslCa);

        // 1. If it's a file path that exists, check if it's PEM or DER
        if (file_exists($sslCa)) {
            $content = file_get_contents($sslCa);
            if ($content === false || strlen($content) < 50) {
                return null;
            }

            // Already PEM? Use directly
            if (str_starts_with(trim($content), "-----BEGIN CERTIFICATE-----")) {
                return $sslCa;
            }

            // File exists but contains DER — convert to PEM
            return self::convertDerToPem($content, $tempDir, $hash) ?? $sslCa;
        }

        // 2. If it starts with "-----BEGIN", it's inline PEM text
        if (str_starts_with(trim($sslCa), "-----BEGIN CERTIFICATE-----")) {
            $pemFile = "$tempDir/aiven-ca-$hash.pem";
            file_put_contents($pemFile, $sslCa);
            return filesize($pemFile) > 0 ? $pemFile : null;
        }

        // 3. Try base64 decode
        $decoded = base64_decode($sslCa, true);
        if ($decoded === false || strlen($decoded) < 50) {
            return null;
        }

        // Might be base64-encoded PEM text
        if (str_starts_with(trim($decoded), "-----BEGIN CERTIFICATE-----")) {
            $pemFile = "$tempDir/aiven-ca-$hash.pem";
            file_put_contents($pemFile, trim($decoded));
            return filesize($pemFile) > 0 ? $pemFile : null;
        }

        // It's DER binary — convert to PEM
        return self::convertDerToPem($decoded, $tempDir, $hash);
    }

    /**
     * Convert DER binary certificate to PEM file.
     */
    private static function convertDerToPem(string $derContent, string $tempDir, string $hash): ?string
    {
        $derFile = "$tempDir/aiven-ca-$hash.der";
        $pemFile = "$tempDir/aiven-ca-$hash.pem";
        file_put_contents($derFile, $derContent);

        // Method 1: PHP openssl functions
        $cert = @openssl_x509_read($derFile);
        if ($cert !== false) {
            if (@openssl_x509_export($cert, $pemContent) && !empty($pemContent)) {
                file_put_contents($pemFile, $pemContent);
                @openssl_x509_free($cert);
                @unlink($derFile);
                return $pemFile;
            }
            @openssl_x509_free($cert);
        }

        // Method 2: openssl CLI (different flags)
        exec("openssl x509 -inform DER -in " . escapeshellarg($derFile) . " -out " . escapeshellarg($pemFile) . " 2>&1", $output, $exitCode);
        if ($exitCode === 0 && file_exists($pemFile) && filesize($pemFile) > 0) {
            @unlink($derFile);
            return $pemFile;
        }

        @unlink($derFile);
        @unlink($pemFile);
        return null;
    }
}
