<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core\Container;

interface ContainerInterface {
    public function get(string $id);
    public function has(string $id): bool;
    public function set(string $id, $service): void;
    public function factory(string $id, callable $factory): void;
}
