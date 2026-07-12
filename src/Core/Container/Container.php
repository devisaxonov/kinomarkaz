<?php

namespace App\Core\Container;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use App\Services\TelegramService;

class Container
{
    private array $instances = [];
    private array $bindings = [];

    public function bind(string $abstract, $concrete = null): void
    {
        $this->bindings[$abstract] = $concrete ?? $abstract;
    }

    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete);
    }

    public function get(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;



        if ($concrete instanceof \Closure) {
            $object = $concrete($this);
            $this->instances[$abstract] = $object;
            return $object;
        }

        $object = $this->resolve($concrete);
        
        $this->instances[$abstract] = $object;
        
        return $object;
    }

    private function resolve(string $concrete)
    {
        try {
            $reflection = new ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new ContainerException("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Target class [$concrete] is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        return $reflection->newInstanceArgs($dependencies);
    }

    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new ContainerException("Cannot resolve class dependency {$parameter->name}");
            }

            $dependencies[] = $this->get($type->getName());
        }

        return $dependencies;
    }
}
