<?php

namespace App\Services;

use App\Core\Database\Database;

class SearchService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findMovieByCode(string $code): ?array
    {
        $movie = $this->db->table('movies')
                          ->where('code', $code)
                          ->first();

        if ($movie) {
            $this->incrementViews($code);
        }

        return $movie;
    }

    public function getTopMovies(int $limit = 10): array
    {
        return $this->db->table('movies')
                        ->orderBy('views', 'DESC')
                        ->limit($limit)
                        ->get(['title', 'code', 'views']);
    }

    private function incrementViews(string $code): void
    {
        // Simple synchronous query to increment views
        // With the +10000 boost logic if views are between 100 and 1000
        $sql = "UPDATE movies 
                SET views = CASE 
                    WHEN views >= 100 AND views < 1000 THEN views + 10001
                    ELSE views + 1 
                END
                WHERE code = :code";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute(['code' => $code]);
    }

    public function logSearchHistory(int $userId, string $code, ?int $movieId): void
    {
        $dir = __DIR__ . '/../../../storage';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        $logData = json_encode([
            'date' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'search_query' => $code,
            'movie_id' => $movieId
        ]) . PHP_EOL;
        
        file_put_contents($dir . '/search_history.log', $logData, FILE_APPEND);
    }
}
