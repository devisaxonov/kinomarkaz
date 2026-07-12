<?php

namespace App\Core\Database;

use PDO;

class QueryBuilder
{
    private PDO $pdo;
    private string $table;
    
    private array $wheres = [];
    private array $bindings = [];
    private ?int $limit = null;
    private string $order = '';

    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function where(string $column, $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = "$column $operator ?";
        $this->bindings[] = $value;

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->order = "ORDER BY $column " . strtoupper($direction);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function first(): ?array
    {
        $this->limit = 1;
        $results = $this->get();
        return count($results) > 0 ? $results[0] : null;
    }

    public function get(array $columns = ['*']): array
    {
        $cols = implode(', ', $columns);
        $sql = "SELECT $cols FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        if (!empty($this->order)) {
            $sql .= " {$this->order}";
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->fetchAll();
    }

    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    public function update(array $data): bool
    {
        $setClauses = [];
        $updateBindings = [];

        foreach ($data as $column => $value) {
            $setClauses[] = "$column = ?";
            $updateBindings[] = $value;
        }

        $setSql = implode(', ', $setClauses);
        $sql = "UPDATE {$this->table} SET $setSql";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
            $updateBindings = array_merge($updateBindings, $this->bindings);
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($updateBindings);
    }
    
    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->bindings);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);

        return (int) $stmt->fetchColumn();
    }
}
