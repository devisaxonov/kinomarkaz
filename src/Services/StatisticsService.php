<?php

namespace App\Services;

use App\Core\Database\Database;
use Redis;

class StatisticsService
{
    private Database $db;
    private Redis $redis;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->redis = new Redis();
        $this->redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', 6379);
    }

    public function getAdminReport(): string
    {
        $usersCount = $this->db->table('users')->count();
        $moviesCount = $this->db->table('movies')->count();
        $pendingViews = $this->redis->lLen('queue:movie_views');
        
        return "📊 <b>Bot Statistikasi</b>\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "👤 Jami foydalanuvchilar: <b>{$usersCount}</b>\n"
             . "🎬 Jami kinolar: <b>{$moviesCount}</b>\n"
             . "🔄 Kutilayotgan jarayonlar: <b>{$pendingViews}</b> ta\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "⚡️ <i>Pure PHP Architecture Engine</i>";
    }
}
