<?php

namespace App\Http\Middlewares;

use App\Services\TelegramService;
use App\Repositories\ChannelRepository;
use App\Core\Localization\Lang;

class CheckSubscriptionMiddleware
{
    private TelegramService $telegram;
    private ChannelRepository $channelRepo;
    private string $cacheFile;

    public function __construct(TelegramService $telegram, ChannelRepository $channelRepo)
    {
        $this->telegram = $telegram;
        $this->channelRepo = $channelRepo;
        $this->cacheFile = __DIR__ . '/../../../storage/sub_cache.json';
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!file_exists($this->cacheFile)) {
            file_put_contents($this->cacheFile, json_encode([]));
        }
    }

    private function getCache(): array
    {
        $content = file_get_contents($this->cacheFile);
        return $content ? json_decode($content, true) : [];
    }

    private function saveCache(array $data): void
    {
        file_put_contents($this->cacheFile, json_encode($data));
    }

    public function check(int $userId): bool
    {
        $channels = $this->channelRepo->getAllCached();
        if (empty($channels)) {
            return true;
        }

        $cache = $this->getCache();

        if (isset($cache[$userId]) && (time() - $cache[$userId] < 900)) {
            return true; // Valid for 15 minutes
        }

        foreach ($channels as $channelRow) {
            $username = $channelRow['username'];
            $response = $this->telegram->getChatMember($username, $userId);
            
            $status = $response['result']['status'] ?? 'left';
            if (in_array($status, ['left', 'kicked'])) {
                return false; 
            }
        }

        $cache[$userId] = time();
        
        // Cleanup old cache entries
        foreach ($cache as $id => $time) {
            if (time() - $time > 900) {
                unset($cache[$id]);
            }
        }

        $this->saveCache($cache);
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

        $inlineKeyboard[] = [
            ['text' => "✅ Tasdiqlash", 'callback_data' => 'check_sub']
        ];

        $this->telegram->sendMessage($chatId, Lang::get('must_subscribe'), ['inline_keyboard' => $inlineKeyboard]);
    }
}
