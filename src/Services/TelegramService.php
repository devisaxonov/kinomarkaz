<?php

namespace App\Services;

class TelegramService
{
    private string $apiUrl;

    public function __construct()
    {
        $token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";
    }

    private function request(string $method, array $data = []): ?array
    {
        $ch = curl_init($this->apiUrl . $method);
        
        $payload = json_encode($data);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Connection: Keep-Alive'
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $logFile = __DIR__ . '/../../../storage/error.log';

        if ($error) {
            file_put_contents($logFile, "Curl Error: $error\n", FILE_APPEND);
            return null;
        }

        if ($httpCode >= 400) {
            file_put_contents($logFile, "Telegram API Error ($httpCode): $response\nPayload: $payload\n", FILE_APPEND);
        }

        return json_decode($response, true);
    }

    public function sendMessage(int $chatId, string $text, array $replyMarkup = null): void
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($replyMarkup) {
            $data['reply_markup'] = $replyMarkup;
        }

        $this->request('sendMessage', $data);
    }

    public function copyMessage(int $chatId, int $fromChatId, int $messageId, array $replyMarkup = null, ?string $caption = null, string $parseMode = 'HTML'): void
    {
        $data = [
            'chat_id' => $chatId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId,
        ];

        if ($replyMarkup) {
            $data['reply_markup'] = $replyMarkup;
        }

        if ($caption !== null) {
            $data['caption'] = $caption;
            $data['parse_mode'] = $parseMode;
        }

        $this->request('copyMessage', $data);
    }

    public function deleteMessage(int $chatId, int $messageId): void
    {
        $this->request('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    public function getChatMember(string $channelId, int $userId): ?array
    {
        return $this->request('getChatMember', [
            'chat_id' => $channelId,
            'user_id' => $userId,
        ]);
    }

    public function setMyCommands(array $commands, array $scope = null): void
    {
        $data = ['commands' => $commands];
        if ($scope) {
            $data['scope'] = $scope;
        }
        $this->request('setMyCommands', $data);
    }
}
