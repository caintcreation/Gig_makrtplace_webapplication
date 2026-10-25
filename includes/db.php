<?php
// includes/db.php - Database connection using PDO
// Configured for both local development and live hosting (e.g. InfinityFree)

if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) return;
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                $val = trim($val, "\"'");
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $val;
                    putenv("$key=$val");
                }
            }
        }
    }
}

// 1. Try to load from .env file if available
loadEnv(__DIR__ . '/../.env');

// 2. Direct Fallback Configuration (Optional: Edit these values directly when hosting on InfinityFree)
// InfinityFree Database Host is typically sqlXXX.infinityfree.com (find it in your vPanel)
defined('HOSTING_DB_HOST') or define('HOSTING_DB_HOST', '');
defined('HOSTING_DB_NAME') or define('HOSTING_DB_NAME', '');
defined('HOSTING_DB_USER') or define('HOSTING_DB_USER', '');
defined('HOSTING_DB_PASS') or define('HOSTING_DB_PASS', '');

function getDb(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: (HOSTING_DB_HOST ?: '127.0.0.1');
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: (HOSTING_DB_NAME ?: 'gigmarket');
        $user = getenv('DB_USER') ?: (HOSTING_DB_USER ?: 'root');
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (HOSTING_DB_PASS ?: '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die('Database connection error: ' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}
