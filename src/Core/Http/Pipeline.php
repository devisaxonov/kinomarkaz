<?php

namespace App\Core\Http;

use Closure;
use App\Core\Container\Container;

class Pipeline
{
    private array $middlewares = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function through(array $middlewares): self
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    public function then(Closure $destination, Request $request): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function (Closure $next, string $middleware) {
                return function (Request $request) use ($next, $middleware) {
                    $instance = $this->container->get($middleware);
                    return $instance->handle($request, $next);
                };
            },
            $destination
        );

        return $pipeline($request);
    }
}
