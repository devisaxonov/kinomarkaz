<?php

namespace App\Jobs;

use App\Core\Database\Database;
use Redis;

class MovieViewsJob
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function handle(Redis $redis): void
    {
        $queueName = 'queue:movie_views';
        $batchSize = 100; 

        $length = $redis->lLen($queueName);
        if ($length === 0) {
            return;
        }

        $itemsToProcess = min($length, $batchSize);
        $viewsToUpdate = [];

        for ($i = 0; $i < $itemsToProcess; $i++) {
            $code = $redis->lPop($queueName);
            if ($code) {
                if (!isset($viewsToUpdate[$code])) {
                    $viewsToUpdate[$code] = 0;
                }
                $viewsToUpdate[$code]++;
            }
        }

        foreach ($viewsToUpdate as $code => $count) {
            $sql = "UPDATE movies 
                    SET views = CASE 
                        WHEN views + ? >= 100 AND views + ? <= 1000 THEN views + ? + 10000 
                        ELSE views + ? 
                    END 
                    WHERE code = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$count, $count, $count, $count, $code]);
        }

        echo "✅ [$itemsToProcess] ta kino statistikasi yangilandi.\n";
    }
}
