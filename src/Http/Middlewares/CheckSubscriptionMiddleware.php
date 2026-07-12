<?php

namespace App\Http\Middlewares;

use App\Services\TelegramService;
use App\Repositories\ChannelRepository;
use App\Core\Localization\Lang;
use Redis;

class CheckSubscriptionMiddleware
{
    private TelegramService $telegram;
    private ChannelRepository $channelRepo;
    private Redis $redis;

    public function __construct(TelegramService $telegram, ChannelRepository $channelRepo)
    {
        $this->telegram = $telegram;
        $this->channelRepo = $channelRepo;
        $this->redis = new Redis();
        $this->redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', 6379);
    }

    public function check(int $userId): bool
    {
        $channels = $this->channelRepo->getAllCached();
        if (empty($channels)) {
            return true;
        }

        $cacheKey = "sub_status:{$userId}";

        // Keshni tekshiramiz (15 daqiqada 1 marta Telegram API ga murojaat qilinadi)
        if ($this->redis->get($cacheKey)) {
            return true;
        }

        foreach ($channels as $channelRow) {
            $username = $channelRow['username'];
            $response = $this->telegram->getChatMember($username, $userId);
            
            $status = $response['result']['status'] ?? 'left';
            if (in_array($status, ['left', 'kicked'])) {
                return false; 
            }
        }

        // Hamma kanalga a'zo bo'lsa, 15 daqiqaga keshlanadi
        $this->redis->setex($cacheKey, 900, 'subscribed');
        return true;
    }

    public function sendSubscriptionWarning(int $chatId): void
    {
        $channels = $this->channelRepo->getAllCached();

        $inlineKeyboard = [];
        foreach ($channels as $index => $channelRow) {
            $title = $channelRow['title'] ?? ($index + 1) . "-kanal";
            $link = $channelRow['link'];
            $inlineKeyboard[] = [
                ['text' => "📢 " . $title, 'url' => $link]
            ];
        }

        // Tasdiqlash tugmasi
        $inlineKeyboard[] = [
            ['text' => "✅ Tasdiqlash", 'callback_data' => 'check_sub']
        ];

        $this->telegram->sendMessage($chatId, Lang::get('must_subscribe'), ['inline_keyboard' => $inlineKeyboard]);
    }
}
