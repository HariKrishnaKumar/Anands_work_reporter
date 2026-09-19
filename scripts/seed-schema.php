#!/usr/bin/env php
<?php
/**
 * Schema Seeder — PHP-based fallback for Docker entrypoint.
 * Uses the same Database.php SSL logic that already works for the app.
 * 
 * Usage: php /var/www/html/scripts/seed-schema.php
 */

$rootDir = dirname(__DIR__);

// Load .env
$envFile = $rootDir . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Remove surrounding quotes
        if (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value)-1] === '"') {
            $value = substr($value, 1, -1);
        } elseif (strlen($value) >= 2 && $value[0] === "'" && $value[strlen($value)-1] === "'") {
            $value = substr($value, 1, -1);
        }
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

$host     = $_ENV['DB_HOST']     ?? '';
$port     = $_ENV['DB_PORT']     ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? '';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

if (!$host || !$database || !$username || !$password) {
    echo "[seed] Missing DB env vars — skipping\n";
    exit(0);
}

echo "[seed] Connecting to $host:$port/$database...\n";

// Use the same SSL resolution logic as Database.php
$sslCa = $_ENV['DB_SSL_CA'] ?? '';
$sslVerify = ($_ENV['DB_SSL_VERIFY'] ?? 'true') === 'true';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

if (!empty($sslCa)) {
    $sslCaPath = resolveSslCa($sslCa);
    if ($sslCaPath !== null) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCaPath;
        if ($sslVerify) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }
        echo "[seed] Using SSL cert: $sslCaPath\n";
    } else {
        echo "[seed] WARNING: Could not resolve SSL cert — trying without\n";
    }
}

$dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "[seed] Connected successfully\n";
} catch (PDOException $e) {
    echo "[seed] FAILED to connect: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if users table exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'users'");
$stmt->execute([$database]);
$count = (int) $stmt->fetchColumn();

if ($count > 0) {
    echo "[seed] Users table already exists — schema is seeded\n";
    exit(0);
}

echo "[seed] Users table not found — seeding schema...\n";

// Read and execute schema
$schemaFile = $rootDir . '/database/schema.sql';
if (!file_exists($schemaFile)) {
    echo "[seed] ERROR: schema.sql not found at $schemaFile\n";
    exit(1);
}

$schema = file_get_contents($schemaFile);
// Remove USE and CREATE DATABASE statements (not needed, we're using the right DB)
$schema = preg_replace('/^USE\s+\S+;?\s*$/mi', '', $schema);
$schema = preg_replace('/^CREATE\s+DATABASE\s+.+;?\s*$/mi', '', $schema);

try {
    $pdo->exec($schema);
    echo "[seed] Schema seeding: SUCCESS (3 tables + 3 test users created)\n";
} catch (PDOException $e) {
    echo "[seed] Schema seeding: FAILED — " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Resolve SSL CA certificate to a valid PEM file path.
 * (Copied from Database.php for standalone use)
 */
function resolveSslCa(string $sslCa): ?string
{
    $tempDir = sys_get_temp_dir();

    // 1. File path that exists
    if (file_exists($sslCa)) {
        $content = file_get_contents($sslCa);
        if ($content === false || strlen($content) < 50) return null;

        if (str_starts_with(trim($content), "-----BEGIN CERTIFICATE-----")) {
            return $sslCa;
        }

        // DER file? Convert to PEM
        $pemFile = tempnam($tempDir, 'ssl_');
        if (openssl_x509(file_get_contents($sslCa), FILE_NO_DEFAULT_CONTEXT, OPENSSL_RAW_DATA, ['output_type' => OPENSSL_TEXTPROC_NONE])) {
            // It's DER
            $cert = openssl_x509_read(file_get_contents($sslCa));
            if ($cert) {
                openssl_x509_export($cert, $pemContent);
                file_put_contents($pemFile, $pemContent);
                return $pemFile;
            }
        }

        // Try treating as PEM directly
        $cert = openssl_x509_read($content);
        if ($cert) return $sslCa;

        return null;
    }

    // 2. PEM text
    if (str_starts_with(trim($sslCa), "-----BEGIN CERTIFICATE-----")) {
        $pemFile = tempnam($tempDir, 'ssl_');
        file_put_contents($pemFile, $sslCa);
        return $pemFile;
    }

    // 3. Base64-encoded DER
    $clean = preg_replace('/\s+/', '', $sslCa);
    $der = base64_decode($clean, true);
    if ($der !== false && strlen($der) > 50) {
        $cert = openssl_x509_read($der);
        if ($cert) {
            openssl_x509_export($cert, $pemContent);
            $pemFile = tempnam($tempDir, 'ssl_');
            file_put_contents($pemFile, $pemContent);
            return $pemFile;
        }
    }

    return null;
}
