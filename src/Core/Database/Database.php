<?php

namespace App\Core\Database;

use mysqli;
use RuntimeException;

class Database
{
    private ?mysqli $mysqli = null;

    public function getConnection(): mysqli
    {
        if ($this->mysqli === null) {
            $this->connect();
        }
        
        return $this->mysqli;
    }

    private function connect(): void
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $db   = $_ENV['DB_DATABASE'] ?? 'kino_bot';
        $user = $_ENV['DB_USERNAME'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->mysqli = new mysqli($host, $user, $pass, $db, (int)$port);
            $this->mysqli->set_charset("utf8mb4");
        } catch (\mysqli_sql_exception $e) {
            throw new RuntimeException("Database Connection Error: " . $e->getMessage());
        }
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this->getConnection(), $table);
    }
}
