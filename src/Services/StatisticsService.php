<?php

namespace App\Services;

use App\Core\Database\Database;

class StatisticsService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getAdminReport(): string
    {
        $usersCount = $this->db->table('users')->count();
        $moviesCount = $this->db->table('movies')->count();
        $pendingBroadcasts = $this->db->table('broadcast_queue')->count();
        
        return "📊 <b>Bot Statistikasi</b>\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "👤 Jami foydalanuvchilar: <b>{$usersCount}</b>\n"
             . "🎬 Jami kinolar: <b>{$moviesCount}</b>\n"
             . "🔄 Kutilayotgan reklama xabarlari: <b>{$pendingBroadcasts}</b> ta\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "⚡️ <i>Pure PHP Architecture Engine</i>";
    }
}
