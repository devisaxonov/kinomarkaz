<?php

namespace App\Telegram\Handlers;

use App\Services\TelegramService;
use App\Services\StatisticsService;
use App\Core\FSM\WizardState;
use App\Core\Localization\Lang;
use App\Repositories\ChannelRepository;
use App\Repositories\SettingsRepository;

class AdminHandler
{
    private TelegramService $telegram;
    private StatisticsService $statistics;
    private WizardState $wizardState;
    private ChannelRepository $channelRepo;
    private SettingsRepository $settingsRepo;

    public function __construct(
        TelegramService $telegram, 
        StatisticsService $statistics,
        WizardState $wizardState,
        ChannelRepository $channelRepo,
        SettingsRepository $settingsRepo
    ) {
        $this->telegram = $telegram;
        $this->statistics = $statistics;
        $this->wizardState = $wizardState;
        $this->channelRepo = $channelRepo;
        $this->settingsRepo = $settingsRepo;
    }

    public function handle(int $chatId, int $userId, array $message): void
    {
        $text = trim($message['text'] ?? '');

        if ($text === '/admin') {
            $this->sendAdminPanel($chatId);
            return;
        }

        if ($text === '/stats') {
            $report = $this->statistics->getAdminReport();
            $this->telegram->sendMessage($chatId, $report);
            return;
        }

        if ($text === '/addmovie') {
            // FSM ning 1-qadamini boshlaymiz
            $this->wizardState->set($userId, 'ask_video', []);
            $this->telegram->sendMessage($chatId, Lang::get('admin_wizard_start'));
            return;
        }

        if ($text === '/broadcast') {
            $this->wizardState->set($userId, 'ask_broadcast', []);
            $this->telegram->sendMessage($chatId, "📣 <b>Reklama yuborish</b>\n\nTarqatmoqchi bo'lgan xabaringizni yuboring (Rasm, video yoki matn bo'lishi mumkin).\n\n<i>Bekor qilish uchun /cancel ni bosing.</i>");
            return;
        }

        if (str_starts_with($text, '/setcodechannel ')) {
            $parts = explode(' ', $text);
            if (count($parts) === 2 && str_starts_with($parts[1], '@')) {
                $this->settingsRepo->set('main_code_channel', $parts[1]);
                $this->telegram->sendMessage($chatId, "✅ Asosiy kino kodlari kanali <b>{$parts[1]}</b> qilib belgilandi!");
            } else {
                $this->telegram->sendMessage($chatId, "Noto'g'ri format! Misol: /setcodechannel @kino_kodlari");
            }
            return;
        }

        if ($text === '/delcodechannel') {
            $this->settingsRepo->delete('main_code_channel');
            $this->telegram->sendMessage($chatId, "✅ Asosiy kino kodlari kanali o'chirildi.");
            return;
        }

        if ($text === '/setupcommands') {
            // Oddiy userlar uchun
            $this->telegram->setMyCommands([
                ['command' => 'start', 'description' => 'Botni qayta ishga tushirish'],
                ['command' => 'top', 'description' => 'Eng ko\'p qidirilgan kinolar'],
                ['command' => 'help', 'description' => 'Botdan foydalanish qo\'llanmasi']
            ], ['type' => 'default']);

            // Admin uchun
            $this->telegram->setMyCommands([
                ['command' => 'admin', 'description' => 'Admin Panelni ochish'],
                ['command' => 'addmovie', 'description' => 'Yangi Kino/Serial qo\'shish'],
                ['command' => 'channels', 'description' => 'Kanallarni boshqarish'],
                ['command' => 'broadcast', 'description' => 'Reklama tarqatish'],
                ['command' => 'stats', 'description' => 'Statistikani ko\'rish'],
                ['command' => 'cancel', 'description' => 'Jarayonni bekor qilish']
            ], ['type' => 'chat', 'chat_id' => $chatId]);

            $this->telegram->sendMessage($chatId, "✅ Buyruqlar menyusi (Menu) muvaffaqiyatli yangilandi!\n\nIlovani qayta ochib Menu ni tekshiring. Endi oddiy odamlar /admin ni ko'ra olmaydi.");
            return;
        }

        if (str_starts_with($text, '/addchannel')) {
            $parts = explode(' ', $text);
            if (count($parts) < 2) {
                $this->telegram->sendMessage($chatId, "Format xato. Namuna: /addchannel @kanal_nomi Kanal_Sarlavhasi");
                return;
            }
            $username = $parts[1];
            $title = implode(' ', array_slice($parts, 2)) ?: 'Kanal';
            $this->channelRepo->add($username, $title);
            $this->telegram->sendMessage($chatId, "✅ Kanal muvaffaqiyatli qo'shildi: $username");
            return;
        }

        if (str_starts_with($text, '/delchannel')) {
            $parts = explode(' ', $text);
            if (count($parts) < 2) {
                $this->telegram->sendMessage($chatId, "Format xato. Namuna: /delchannel @kanal_nomi");
                return;
            }
            $this->channelRepo->remove($parts[1]);
            $this->telegram->sendMessage($chatId, "🗑 Kanal o'chirildi.");
            return;
        }

        if ($text === '/channels') {
            $channels = $this->channelRepo->getAllCached();
            $msg = "📢 <b>Majburiy obuna kanallari:</b>\n\n";
            foreach ($channels as $ch) {
                $msg .= "— {$ch['title']} ({$ch['username']})\n";
            }
            if (empty($channels)) $msg .= "Hozircha kanallar yo'q.";
            
            $mainCodeChannel = $this->settingsRepo->get('main_code_channel');
            $msg .= "\n\n🎬 <b>Asosiy kino kodlari kanali:</b>\n";
            $msg .= $mainCodeChannel ? $mainCodeChannel : "O'rnatilmagan.";
            
            $this->telegram->sendMessage($chatId, $msg);
            return;
        }
        
        // Agar yozgan narsasi buyruq bo'lmasa
        $this->telegram->sendMessage($chatId, "Boshqaruv paneli uchun /admin ni bosing.");
    }

    private function sendAdminPanel(int $chatId): void
    {
        $text = "👑 <b>Admin Panelga xush kelibsiz!</b>\n\nQuyidagi buyruqlardan birini tanlang:\n"
              . "➕ /addmovie - Yangi kino yoki serial qo'shish\n"
              . "📊 /stats - Bot statistikasi\n"
              . "📢 /channels - Kanallar ro'yxati\n"
              . "📣 /broadcast - Barcha foydalanuvchilarga xabar tarqatish\n"
              . "➕ /addchannel @kanal_nomi Sarlavha - Majburiy obuna kanalini qo'shish\n"
              . "🗑 /delchannel @kanal_nomi - Majburiy obuna kanalini o'chirish\n"
              . "🔗 /setcodechannel @kanal_nomi - Asosiy kino kodlari kanalini ulash\n"
              . "❌ /delcodechannel - Asosiy kino kodlari kanalini uzish\n"
              . "⚙️ /setupcommands - Bot menyusidagi buyruqlarni yangilash\n"
              . "❌ /cancel - Jarayonlarni bekor qilish";

        $this->telegram->sendMessage($chatId, $text);
    }
}
