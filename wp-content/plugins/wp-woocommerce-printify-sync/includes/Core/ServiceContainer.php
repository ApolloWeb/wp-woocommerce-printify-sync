<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class ServiceContainer {
    private $services = [];
    private $factories = [];
    private $instances = [];
    private $status = [];

    public function register(string $id, callable $factory): void {
        $this->factories[$id] = $factory;
    }

    public function get(string $id) {
        if (!isset($this->instances[$id])) {
            if (!isset($this->factories[$id])) {
                throw new \RuntimeException("Service not found: $id");
            }
            $this->instances[$id] = $this->factories[$id]();
            $this->status[$id] = ['initialized' => current_time('mysql')];
        }
        return $this->instances[$id];
    }

    public function has(string $id): bool {
        return isset($this->factories[$id]);
    }

    public function getStatus(): array {
        return $this->status;
    }
}
