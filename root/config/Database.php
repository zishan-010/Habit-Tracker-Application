<?php
/**
 * Database.php
 * Secure, reusable PDO connection for the Habit Tracker app.
 *
 * Security features:
 *  - Credentials pulled from environment variables / .env, never hardcoded
 *  - PDO with prepared statements enforced (no emulated prepares)
 *  - Exceptions instead of silent failures (PDO::ERRMODE_EXCEPTION)
 *  - Errors logged, not echoed (prevents leaking DB structure/creds to users)
 *  - Singleton pattern so only one connection is opened per request
 *  - utf8mb4 charset to avoid encoding-based injection tricks
 */

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    // Prevent instantiation / cloning from outside
    private function __construct()
    {
    }
    private function __clone()
    {
    }

    /**
     * Loads simple KEY=VALUE pairs from a .env file into memory.
     * (Lightweight alternative to vlucas/phpdotenv — swap this out
     * for a real library if you're using Composer.)
     */
    private static function loadEnv(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $line, 2), 2, null);
            if ($key === null || $value === null) {
                continue;
            }
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
            }
        }
    }

    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::loadEnv(__DIR__ . '/.env'); // config/.env — same folder as this file

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: '';
        $user = getenv('DB_USER') ?: '';
        $pass = getenv('DB_PASS') ?: '';

        if ($dbname === '' || $user === '') {
            // Fail loudly to the log, generically to the user
            error_log('Database config error: DB_NAME or DB_USER not set.');
            throw new RuntimeException('Server configuration error.');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // throw on error, don't fail silently
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // predictable array shape
            PDO::ATTR_EMULATE_PREPARES => false,                 // real prepared statements -> strongest injection protection
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ];

        try {
            self::$instance = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Log full detail server-side, show nothing sensitive to the client
            error_log('DB connection failed: ' . $e->getMessage());
            throw new RuntimeException('Unable to connect to the database.');
        }

        return self::$instance;
    }
}