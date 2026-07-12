<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Redis;

class BroadcastJob
{
    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    public function handle(Redis $redis): void
    {
        $queueName = 'queue:broadcast';
        $batchSize = 25; 
        
        $length = $redis->lLen($queueName);
        if ($length === 0) {
            return;
        }

        $itemsToProcess = min($length, $batchSize);

        for ($i = 0; $i < $itemsToProcess; $i++) {
            $dataString = $redis->lPop($queueName);
            if ($dataString) {
                $data = json_decode($dataString, true);
                
                $this->telegram->copyMessage(
                    $data['user_id'], 
                    $data['from_channel_id'], 
                    $data['message_id']
                );
            }
        }

        echo "📢 [$itemsToProcess] ta foydalanuvchiga xabar tarqatildi.\n";
    }
}
