<?php

declare(strict_types=1);

namespace Tests;

use App\Config\Database;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        parent::setUp();

        // Fresh in-memory SQLite database for every single test.
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_SQLITE_PATH'] = ':memory:';
        Database::reset();

        $this->db = Database::connection();
    }
}
