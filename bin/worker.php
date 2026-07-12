<?php

declare(strict_types=1);

ini_set('memory_limit', '128M'); 

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Container\Container;
use App\Jobs\MovieViewsJob;
use App\Jobs\BroadcastJob;

$container = new Container();

echo "⚙️ Worker ishga tushdi va vazifalarni kutmoqda...\n";

$redis = new Redis();
$redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', 6379);

while (true) {
    $movieViewsJob = $container->get(MovieViewsJob::class);
    $movieViewsJob->handle($redis);

    $broadcastJob = $container->get(BroadcastJob::class);
    $broadcastJob->handle($redis);

    sleep(1);
    
    gc_collect_cycles();
}
