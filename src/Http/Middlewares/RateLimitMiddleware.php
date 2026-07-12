<?php

namespace App\Http\Middlewares;

use App\Core\Http\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;

class RateLimitMiddleware implements MiddlewareInterface
{
    private const MAX_REQUESTS = 30;
    private const DECAY_MINUTES = 1;
    private string $filePath;

    public function __construct()
    {
        $this->filePath = __DIR__ . '/../../../storage/rate_limit.json';
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    private function getCache(): array
    {
        $content = file_get_contents($this->filePath);
        return $content ? json_decode($content, true) : [];
    }

    private function saveCache(array $data): void
    {
        file_put_contents($this->filePath, json_encode($data));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $payload = $request->all();
        
        $userId = $payload['message']['from']['id'] 
               ?? $payload['callback_query']['from']['id'] 
               ?? null;

        if (!$userId) {
            return $next($request);
        }

        $data = $this->getCache();
        $currentTime = time();
        
        // Cleanup old limits
        $changed = false;
        foreach ($data as $id => $info) {
            if ($currentTime - $info['time'] > self::DECAY_MINUTES * 60) {
                unset($data[$id]);
                $changed = true;
            }
        }

        $userLimit = $data[$userId] ?? ['attempts' => 0, 'time' => $currentTime];
        
        // Reset if time expired
        if ($currentTime - $userLimit['time'] > self::DECAY_MINUTES * 60) {
            $userLimit = ['attempts' => 0, 'time' => $currentTime];
        }

        if ($userLimit['attempts'] >= self::MAX_REQUESTS) {
            if ($changed) $this->saveCache($data);
            return (new Response())->json([
                'status' => 'error',
                'message' => 'Too Many Requests (Flood Protection)'
            ], 200);
        }

        $userLimit['attempts']++;
        $data[$userId] = $userLimit;
        
        $this->saveCache($data);

        return $next($request);
    }
}
