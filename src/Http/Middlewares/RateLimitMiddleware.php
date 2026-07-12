<?php

namespace App\Http\Middlewares;

use App\Core\Http\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;
use Redis;

class RateLimitMiddleware implements MiddlewareInterface
{
    private Redis $redis;
    private const MAX_REQUESTS = 30;
    private const DECAY_MINUTES = 1;

    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', 6379);
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

        $key = "rate_limit:user:{$userId}";
        
        $attempts = (int) $this->redis->get($key);

        if ($attempts >= self::MAX_REQUESTS) {
            return (new Response())->json([
                'status' => 'error',
                'message' => 'Too Many Requests (Flood Protection)'
            ], 200);
        }

        $this->redis->incr($key);
        
        if ($attempts === 0) {
            $this->redis->expire($key, self::DECAY_MINUTES * 60);
        }

        return $next($request);
    }
}
