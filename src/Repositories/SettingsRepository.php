<?php

namespace App\Repositories;

use App\Core\Database\Database;

class SettingsRepository
{
    private Database $db;
    private string $table = 'settings';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function get(string $key, $default = null)
    {
        $result = $this->db->table($this->table)
            ->where('key', $key)
            ->first();

        return $result ? $result['value'] : $default;
    }

    public function set(string $key, string $value): bool
    {
        $exists = $this->get($key);
        if ($exists !== null) {
            return $this->db->table($this->table)
                ->where('key', $key)
                ->update(['value' => $value]);
        }

        return $this->db->table($this->table)->insert([
            'key' => $key,
            'value' => $value
        ]);
    }

    public function delete(string $key): bool
    {
        return $this->db->table($this->table)
            ->where('key', $key)
            ->delete();
    }
}
