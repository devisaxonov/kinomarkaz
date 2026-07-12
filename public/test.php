<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Core\Container\Container;
use App\Core\Routing\Router;
use App\Core\Http\Request;

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/webhook/telegram';
$_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';

$json = json_encode(['message' => ['chat' => ['id' => 5015312310], 'from' => ['id' => 5015312310], 'text' => '/start']]);
file_put_contents('php://memory', $json); // Can't easily mock php://input, but we can bypass it by injecting the json directly.

class MockRequest extends Request {
    public function __construct($json) {
        parent::__construct([], [], $_SERVER);
        $reflection = new ReflectionClass(Request::class);
        $prop = $reflection->getProperty('json');
        $prop->setAccessible(true);
        $prop->setValue($this, json_decode($json, true));
    }
}

$request = new MockRequest($json);
$container = new Container();
$router = new Router($container);
$router->post('/webhook/telegram', [\App\Http\Controllers\TelegramController::class, 'handle']);
$response = $router->resolve($request);
$reflection = new ReflectionClass(\App\Core\Http\Response::class);
$prop = $reflection->getProperty('content');
$prop->setAccessible(true);
echo $prop->getValue($response);
