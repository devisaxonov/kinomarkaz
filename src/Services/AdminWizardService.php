<?php

namespace App\Services;

use App\Core\FSM\WizardState;
use App\Core\Localization\Lang;
use App\Core\Database\Database;

class AdminWizardService
{
    private WizardState $state;
    private TelegramService $telegram;
    private Database $db;

    public function __construct(WizardState $state, TelegramService $telegram, Database $db)
    {
        $this->state = $state;
        $this->telegram = $telegram;
        $this->db = $db;
    }

    public function handle(int $chatId, int $userId, array $message): void
    {
        $text = $message['text'] ?? '';
        $currentState = $this->state->get($userId);

        if ($text === '/cancel') {
            $this->state->clear($userId);
            $this->telegram->sendMessage($chatId, Lang::get('wizard_cancelled'));
            return;
        }

        $step = $currentState['step'];
        $payload = $currentState['payload'];

        switch ($step) {
            case 'ask_broadcast':
                $messageId = $message['message_id'] ?? null;
                if (!$messageId) {
                    $this->telegram->sendMessage($chatId, "Xabar formati noto'g'ri.");
                    return;
                }

                // Bazadagi barcha foydalanuvchilar ID sini olamiz
                $stmt = $this->db->getConnection()->query("SELECT telegram_id FROM users");
                $userIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                $redis = new \Redis();
                $redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', 6379);

                $count = 0;
                foreach ($userIds as $uid) {
                    // O'ziga ham yuborib test qilishi mumkin
                    $data = json_encode([
                        'user_id' => $uid,
                        'from_channel_id' => $chatId,
                        'message_id' => $messageId
                    ]);
                    $redis->rPush('queue:broadcast', $data);
                    $count++;
                }

                $this->state->clear($userId);
                $this->telegram->sendMessage($chatId, "✅ Xabar {$count} ta foydalanuvchiga yuborish uchun navbatga qo'shildi!\nKuting, bu biroz vaqt olishi mumkin.");
                break;
            case 'ask_video':
                $channelId = $message['forward_from_chat']['id'] ?? null;
                $messageId = $message['forward_from_message_id'] ?? null;

                if (!$channelId && !$messageId && !empty($text)) {
                    if (preg_match('/t\.me\/(?:c\/)?([a-zA-Z0-9_]+)\/(\d+)/', $text, $matches)) {
                        $channelStr = $matches[1];
                        $messageId = (int)$matches[2];
                        
                        if (is_numeric($channelStr)) {
                            $channelId = '-100' . $channelStr;
                        } else {
                            $channelId = '@' . $channelStr;
                        }
                    }
                }

                if (!$channelId || !$messageId) {
                    $this->telegram->sendMessage($chatId, "Iltimos, kinoni yopiq kanaldan forward qiling yoki post linkini yuboring (masalan: https://t.me/kanal_nomi/123)");
                    return;
                }

                $payload['channel_id'] = $channelId;
                $payload['message_id'] = $messageId;
                
                $this->state->set($userId, 'ask_title', $payload);
                $this->telegram->sendMessage($chatId, Lang::get('wizard_ask_title'));
                break;
            case 'ask_title':
                $payload['title'] = $text;
                $this->state->set($userId, 'ask_description', $payload);
                $this->telegram->sendMessage($chatId, Lang::get('wizard_ask_description'));
                break;
            case 'ask_description':
                $payload['description'] = $text;
                $this->state->set($userId, 'ask_genre', $payload);
                $this->telegram->sendMessage($chatId, Lang::get('wizard_ask_genre'));
                break;
            case 'ask_genre':
                $payload['genre'] = $text;
                $this->state->set($userId, 'ask_country', $payload);
                $this->telegram->sendMessage($chatId, Lang::get('wizard_ask_country'));
                break;
            case 'ask_country':
                $payload['country'] = $text;
                $this->state->set($userId, 'ask_year', $payload);
                $this->telegram->sendMessage($chatId, Lang::get('wizard_ask_year'));
                break;
            case 'ask_year':
                $payload['year'] = $text;
                $this->state->set($userId, 'ask_language', $payload);
                $this->telegram->sendMessage($chatId, Lang::get('wizard_ask_language'));
                break;
            case 'ask_language':
                $payload['language'] = $text;
                $this->state->set($userId, 'ask_quality', $payload);
                $this->telegram->sendMessage($chatId, Lang::get('wizard_ask_quality'));
                break;
            case 'ask_quality':
                $payload['quality'] = $text;
                $this->state->set($userId, 'ask_duration', $payload);
                $this->telegram->sendMessage($chatId, Lang::get('wizard_ask_duration'));
                break;
            case 'ask_duration':
                $payload['duration'] = $text;
                $this->state->set($userId, 'ask_poster', $payload);
                $this->telegram->sendMessage($chatId, Lang::get('wizard_ask_poster'));
                break;
            case 'ask_poster':
                $photoArray = $message['photo'] ?? [];
                $posterFileId = !empty($photoArray) ? end($photoArray)['file_id'] : null;
                $payload['poster'] = $posterFileId; 
                
                $this->state->set($userId, 'ask_code', $payload);
                $this->telegram->sendMessage($chatId, Lang::get('wizard_ask_code'));
                break;
            case 'ask_code':
                $code = trim($text);
                
                if (strtolower($code) === 'avto') {
                    $code = rand(10000, 99999);
                }

                $exists = $this->db->table('movies')->where('code', $code)->first();
                if ($exists) {
                    $this->telegram->sendMessage($chatId, Lang::get('error_code_exists'));
                    return;
                }

                $payload['code'] = $code;
                $this->saveMovie($payload);
                $this->state->clear($userId);

                $this->telegram->sendMessage($chatId, Lang::get('wizard_success', [
                    'code' => $code,
                    'title' => $payload['title']
                ]));
                break;
        }
    }

    private function saveMovie(array $payload): void
    {
        $this->db->table('movies')->insert([
            'code' => $payload['code'],
            'title' => $payload['title'],
            'description' => $payload['description'],
            'genre' => $payload['genre'],
            'country' => $payload['country'],
            'year' => $payload['year'],
            'language' => $payload['language'],
            'quality' => $payload['quality'],
            'duration' => $payload['duration'],
            'poster' => $payload['poster'],
            'channel_id' => $payload['channel_id'],
            'message_id' => $payload['message_id'],
        ]);
    }
}
