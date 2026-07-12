<?php

namespace App\Core\Database;

use mysqli;

class QueryBuilder
{
    private mysqli $mysqli;
    private string $table;
    
    private array $wheres = [];
    private array $bindings = [];
    private ?int $limit = null;
    private string $order = '';

    public function __construct(mysqli $mysqli, string $table)
    {
        $this->mysqli = $mysqli;
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

    private function getBindTypes(array $bindings): string
    {
        $types = '';
        foreach ($bindings as $binding) {
            if (is_int($binding)) {
                $types .= 'i';
            } elseif (is_float($binding)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
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

        $stmt = $this->mysqli->prepare($sql);
        
        if (!empty($this->bindings)) {
            $types = $this->getBindTypes($this->bindings);
            $stmt->bind_param($types, ...$this->bindings);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result === false) {
            return [];
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
    }

    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

        $stmt = $this->mysqli->prepare($sql);
        
        $values = array_values($data);
        if (!empty($values)) {
            $types = $this->getBindTypes($values);
            $stmt->bind_param($types, ...$values);
        }

        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        return (int) $id;
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

        $stmt = $this->mysqli->prepare($sql);
        
        if (!empty($updateBindings)) {
            $types = $this->getBindTypes($updateBindings);
            $stmt->bind_param($types, ...$updateBindings);
        }

        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $stmt = $this->mysqli->prepare($sql);
        
        if (!empty($this->bindings)) {
            $types = $this->getBindTypes($this->bindings);
            $stmt->bind_param($types, ...$this->bindings);
        }

        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $stmt = $this->mysqli->prepare($sql);
        
        if (!empty($this->bindings)) {
            $types = $this->getBindTypes($this->bindings);
            $stmt->bind_param($types, ...$this->bindings);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        
        $count = 0;
        if ($result !== false && $row = $result->fetch_assoc()) {
            $count = (int)$row['count'];
        }

        $stmt->close();
        return $count;
    }
}
