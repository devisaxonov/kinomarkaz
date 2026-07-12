<?php

namespace App\Repositories;

use App\Core\Database\Database;

class UserRepository
{
    private Database $db;
    private string $table = 'users';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByTelegramId(int $telegramId): ?array
    {
        return $this->db->table($this->table)
            ->where('telegram_id', $telegramId)
            ->first();
    }

    public function create(array $data): int
    {
        return $this->db->table($this->table)->insert([
            'telegram_id' => $data['id'],
            'username' => $data['username'] ?? null,
            'first_name' => $data['first_name'] ?? 'User',
            'language_code' => $data['language_code'] ?? 'uz',
            'is_admin' => 'false',
            'is_banned' => 'false',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function makeAdmin(int $telegramId): bool
    {
        return $this->db->table($this->table)
            ->where('telegram_id', $telegramId)
            ->update(['is_admin' => 'true']);
    }

    public function getAllTelegramIds(): array
    {
        // Pdo query
        $stmt = $this->db->getConnection()->query("SELECT telegram_id FROM {$this->table}");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
