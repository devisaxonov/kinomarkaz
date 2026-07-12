<?php

namespace App\Services;

use App\Core\Database\Database;
use Redis;

class SearchService
{
    private Database $db;
    private Redis $redis;
    private const CACHE_TTL = 86400;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->redis = new Redis();
        $this->redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', 6379);
    }

    public function findMovieByCode(string $code): ?array
    {
        $cacheKey = "movie:{$code}";
        
        $cached = $this->redis->get($cacheKey);
        if ($cached) {
            $this->pushViewToQueue($code);
            return json_decode($cached, true);
        }

        $movie = $this->db->table('movies')
                          ->where('code', $code)
                          ->first();

        if ($movie) {
            $this->redis->setex($cacheKey, self::CACHE_TTL, json_encode($movie, JSON_UNESCAPED_UNICODE));
            $this->pushViewToQueue($code);
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

    private function pushViewToQueue(string $code): void
    {
        $this->redis->rPush('queue:movie_views', $code);
    }

    public function logSearchHistory(int $userId, string $code, ?int $movieId): void
    {
        $logData = json_encode([
            'user_id' => $userId,
            'search_query' => $code,
            'movie_id' => $movieId
        ]);
        
        $this->redis->rPush('queue:search_history', $logData);
    }
}
