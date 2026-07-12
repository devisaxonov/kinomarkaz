<?php

namespace App\Telegram\Handlers;

use App\Services\TelegramService;
use App\Core\Localization\Lang;
use App\Services\SearchService;
use App\Repositories\SettingsRepository;
use App\Core\Validation\Validator;

class UserHandler
{
    private TelegramService $telegram;
    private SearchService $search;
    private SettingsRepository $settingsRepo;

    public function __construct(TelegramService $telegram, SearchService $search, SettingsRepository $settingsRepo)
    {
        $this->telegram = $telegram;
        $this->search = $search;
        $this->settingsRepo = $settingsRepo;
    }

    public function handle(int $chatId, int $userId, array $message, array $userDb): void
    {
        $text = trim($message['text'] ?? '');

        $channelInfo = "";
        $codeChannel = $this->settingsRepo->get('main_code_channel');
        if ($codeChannel) {
            $channelInfo = "\n\n🎬 <i>Kino kodlarini ushbu kanaldan olishingiz mumkin:</i> " . $codeChannel;
        }

        if ($text === '/start') {
            $firstName = htmlspecialchars($userDb['first_name'] ?? 'User');
            $this->telegram->sendMessage(
                $chatId, 
                Lang::get('welcome', ['name' => $firstName]) . $channelInfo
            );
            return;
        }

        if ($text === '/help') {
            $this->telegram->sendMessage($chatId, Lang::get('help') . $channelInfo);
            return;
        }

        if ($text === '/top') {
            $movies = $this->search->getTopMovies(10);
            if (empty($movies)) {
                $this->telegram->sendMessage($chatId, "Hozircha hech qanday kino mavjud emas." . $channelInfo);
                return;
            }
            
            $msg = "🔥 <b>Eng ko'p qidirilgan kinolar top 10 taligi:</b>\n\n";
            $i = 1;
            foreach ($movies as $movie) {
                $title = htmlspecialchars($movie['title']);
                $msg .= "{$i}. {$title} — KOD: <code>{$movie['code']}</code>\n";
                $i++;
            }
            $this->telegram->sendMessage($chatId, $msg . $channelInfo);
            return;
        }

        if (str_starts_with($text, '/')) {
            $this->telegram->sendMessage($chatId, "Noma'lum buyruq. Iltimos, faqat kino kodini yuboring." . $channelInfo);
            return;
        }

        $validator = Validator::make(['code' => $text]);
        if (!$validator->validate(['code' => 'required|string|max:20'])) {
            $this->telegram->sendMessage($chatId, "❌ Noto'g'ri kod formati!" . $channelInfo);
            return;
        }

        $movie = $this->search->findMovieByCode($text);
        $this->search->logSearchHistory($userDb['id'], $text, $movie['id'] ?? null);

        if (!$movie) {
            $this->telegram->sendMessage(
                $chatId, 
                Lang::get('movie_not_found', ['code' => $text]) . $channelInfo
            );
            return;
        }

        $caption = Lang::get('movie_found', [
            'code' => $movie['code'],
            'title' => htmlspecialchars($movie['title']),
            'country' => htmlspecialchars($movie['country']),
            'year' => $movie['year'],
            'language' => htmlspecialchars($movie['language']),
            'quality' => htmlspecialchars($movie['quality']),
            'duration' => htmlspecialchars($movie['duration']),
            'description' => htmlspecialchars($movie['description']),
        ]);

        if ($codeChannel) {
            $caption .= "\n\n🔗 <b>Kino kodlari kanali:</b> " . $codeChannel;
        }

        $this->telegram->copyMessage(
            $chatId, 
            $movie['channel_id'], 
            $movie['message_id'],
            null,
            $caption
        );
    }
}
