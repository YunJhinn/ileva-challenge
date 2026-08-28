<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Builds the single PDO connection used by the app.
 *
 * Driven entirely by environment variables so the same codebase runs either
 * with zero setup locally (SQLite, default) or against MySQL in Docker,
 * without touching a line of application code.
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
            self::migrate(self::$instance);
        }

        return self::$instance;
    }

    private static function connect(): PDO
    {
        $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            if ($driver === 'mysql') {
                $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
                $port = $_ENV['DB_PORT'] ?? '3306';
                $database = $_ENV['DB_DATABASE'] ?? 'contacts';
                $charset = 'utf8mb4';

                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

                return self::connectWithRetry(
                    $dsn,
                    $_ENV['DB_USERNAME'] ?? 'root',
                    $_ENV['DB_PASSWORD'] ?? '',
                    $options
                );
            }

            // Default / fallback: SQLite. Great for local dev and for the
            // automated tests, no server process required.
            $path = $_ENV['DB_SQLITE_PATH'] ?? 'database/database.sqlite';
            $absolutePath = str_starts_with($path, '/') ? $path : dirname(__DIR__, 2) . '/' . $path;

            $directory = dirname($absolutePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            $pdo = new PDO('sqlite:' . $absolutePath, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');

            return $pdo;
        } catch (PDOException $e) {
            throw new RuntimeException('Could not connect to the database: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * MySQL in docker-compose can take a few seconds to accept connections
     * after the container reports "started", so retry briefly instead of
     * failing the very first request.
     */
    private static function connectWithRetry(string $dsn, string $user, string $password, array $options): PDO
    {
        $attempts = 0;
        $maxAttempts = 10;

        while (true) {
            try {
                return new PDO($dsn, $user, $password, $options);
            } catch (PDOException $e) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }
                usleep(500_000);
            }
        }
    }

    private static function migrate(PDO $pdo): void
    {
        $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
        $file = $driver === 'mysql' ? 'schema.mysql.sql' : 'schema.sqlite.sql';
        $path = dirname(__DIR__, 2) . '/database/migrations/' . $file;

        $pdo->exec((string) file_get_contents($path));
    }

    /** Mainly useful for tests, so each test can start from a clean slate. */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
