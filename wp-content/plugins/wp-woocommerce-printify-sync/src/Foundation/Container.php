<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Foundation;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function make(string $abstract)
    {
        // Return existing instance if it's a singleton
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get the concrete implementation
        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;

        // If concrete is a closure, execute it
        if ($concrete instanceof \Closure) {
            $instance = $concrete($this);
        } else {
            $instance = $this->build($concrete);
        }

        // Store the instance if it's a singleton
        if (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared']) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    private function build($concrete)
    {
        $reflector = new \ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = array_map(
            function (\ReflectionParameter $param) {
                $type = $param->getType();
                
                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    return $this->make($type->getName());
                }

                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }

                throw new \Exception("Cannot resolve dependency {$param->getName()}");
            },
            $constructor->getParameters()
        );

        return $reflector->newInstanceArgs($dependencies);
    }
}