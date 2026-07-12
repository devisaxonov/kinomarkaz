<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Redis;

class UserService
{
    private UserRepository $userRepository;
    private Redis $redis;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        
        $this->redis = new Redis();
        $this->redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', 6379);
    }

    public function findOrCreate(array $fromPayload): array
    {
        $telegramId = $fromPayload['id'];
        $cacheKey = "user:{$telegramId}";

        $cachedUser = $this->redis->get($cacheKey);
        
        if ($cachedUser) {
            return json_decode($cachedUser, true);
        }

        $user = $this->userRepository->findByTelegramId($telegramId);

        if (!$user) {
            $insertId = $this->userRepository->create($fromPayload);
            $user = $this->userRepository->findByTelegramId($telegramId);
        }

        $this->redis->setex($cacheKey, 600, json_encode($user));

        return $user;
    }
}
