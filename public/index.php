<?php

declare(strict_types=1);

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\Router;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new Container();

$router = new Router($container);

$router->get('/', function (Request $request) {
    return (new Response())->json(['message' => 'Bot yadro dvigateli ishlamoqda 🚀']);
});

// Telegram Webhook marshruti
$router->post('/webhook/telegram', [\App\Http\Controllers\TelegramController::class, 'handle']);

$request = Request::capture();
$response = $router->resolve($request);
$response->send();
