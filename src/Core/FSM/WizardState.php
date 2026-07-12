<?php

namespace App\Core\FSM;

use Redis;

class WizardState
{
    private Redis $redis;
    private const PREFIX = 'fsm_wizard:';
    private const TTL = 3600;

    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', 6379);
    }

    public function get(int $userId): ?array
    {
        $data = $this->redis->get(self::PREFIX . $userId);
        return $data ? json_decode($data, true) : null;
    }

    public function set(int $userId, string $step, array $payload = []): void
    {
        $data = json_encode([
            'step' => $step,
            'payload' => $payload
        ], JSON_UNESCAPED_UNICODE);

        $this->redis->setex(self::PREFIX . $userId, self::TTL, $data);
    }

    public function clear(int $userId): void
    {
        $this->redis->del(self::PREFIX . $userId);
    }
}
