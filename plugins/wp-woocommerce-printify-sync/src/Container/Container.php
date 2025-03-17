<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Container;

class Container
{
    private array $instances = [];
    private array $factories = [];

    public function singleton(string $abstract, callable $factory): void
    {
        $this->factories[$abstract] = $factory;
    }

    public function get(string $abstract)
    {
        if (!isset($this->instances[$abstract])) {
            if (!isset($this->factories[$abstract])) {
                throw new \RuntimeException("No factory registered for {$abstract}");
            }
            $this->instances[$abstract] = $this->factories[$abstract]($this);
        }

        return $this->instances[$abstract];
    }

    public function has(string $abstract): bool
    {
        return isset($this->factories[$abstract]);
    }
}