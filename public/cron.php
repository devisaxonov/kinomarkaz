<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Core\Database\Database;
use App\Services\TelegramService;

$db = new Database();
$telegram = new TelegramService();

// Reklama yuborish tezligi (Cron 1 daqiqada bir marta ishlaydi)
$limit = 50;

$messages = $db->getConnection()
               ->query("SELECT * FROM broadcast_queue LIMIT $limit")
               ->fetchAll(PDO::FETCH_ASSOC);

if (empty($messages)) {
    echo "No pending broadcasts.";
    exit;
}

foreach ($messages as $msg) {
    try {
        $telegram->copyMessage(
            $msg['user_id'], 
            $msg['from_channel_id'], 
            $msg['message_id']
        );
    } catch (\Exception $e) {
        // Bloklagan foydalanuvchilarni e'tiborsiz qoldirish
    }

    // Yuborib bo'lgach bazadan o'chirib yuboramiz
    $stmt = $db->getConnection()->prepare("DELETE FROM broadcast_queue WHERE id = :id");
    $stmt->execute(['id' => $msg['id']]);
}

echo "Processed " . count($messages) . " broadcasts.";
