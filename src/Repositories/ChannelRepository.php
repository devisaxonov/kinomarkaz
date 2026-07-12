<?php

namespace App\Repositories;

use App\Core\Database\Database;
use Redis;

class ChannelRepository
{
    private Database $db;
    private Redis $redis;
    private string $table = 'channels';

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->redis = new Redis();
        $this->redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', 6379);
    }

    public function getAllCached(): array
    {
        $cacheKey = 'channels_list';
        $cached = $this->redis->get($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        $channels = $this->db->table($this->table)->get();
        $this->redis->setex($cacheKey, 3600, json_encode($channels));

        return $channels;
    }

    public function add(string $username, string $title = 'Kanal'): bool
    {
        try {
            $this->db->table($this->table)->insert([
                'username' => $username,
                'title' => $title,
                'link' => "https://t.me/" . ltrim($username, '@')
            ]);
            $this->redis->del('channels_list');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function remove(string $username): bool
    {
        $this->db->table($this->table)->where('username', $username)->delete();
        $this->redis->del('channels_list');
        return true;
    }
}
