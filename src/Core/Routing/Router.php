<?php

namespace App\Core\Routing;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;

class Router
{
    private array $routes = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $uri, callable|array $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, callable|array $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    private function addRoute(string $method, string $uri, callable|array $action): void
    {
        $this->routes[$method][$uri] = $action;
    }

    public function resolve(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        $action = $this->routes[$method][$uri] ?? null;

        if (!$action) {
            return new Response('Not Found', 404);
        }

        try {
            if (is_callable($action)) {
                return call_user_func($action, $request, $this->container);
            }

            if (is_array($action)) {
                [$class, $methodToCall] = $action;
                $controller = $this->container->get($class);
                return call_user_func([$controller, $methodToCall], $request);
            }

        } catch (\Throwable $e) {
            file_put_contents('/tmp/error.log', "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            return (new Response())->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ], 500);
        }

        return new Response('Error Processing Request', 500);
    }
}
