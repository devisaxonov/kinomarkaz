<?php

namespace App\Repositories;

use App\Core\Database\Database;

class ChannelRepository
{
    private Database $db;
    private string $table = 'channels';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getAllCached(): array
    {
        return $this->db->table($this->table)->get();
    }

    public function add(string $username, string $title = 'Kanal'): bool
    {
        try {
            $this->db->table($this->table)->insert([
                'username' => $username,
                'title' => $title,
                'link' => "https://t.me/" . ltrim($username, '@')
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function remove(string $username): bool
    {
        $this->db->table($this->table)->where('username', $username)->delete();
        return true;
    }
}
