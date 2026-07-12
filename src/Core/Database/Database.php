<?php

namespace App\Core\Database;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private ?PDO $pdo = null;

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        
        return $this->pdo;
    }

    private function connect(): void
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '5432';
        $db   = $_ENV['DB_DATABASE'] ?? 'kino_bot';
        $user = $_ENV['DB_USERNAME'] ?? 'postgres';
        $pass = $_ENV['DB_PASSWORD'] ?? 'secret';

        $dsn = "pgsql:host=$host;port=$port;dbname=$db";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new RuntimeException("Database Connection Error: " . $e->getMessage());
        }
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this->getConnection(), $table);
    }
}
