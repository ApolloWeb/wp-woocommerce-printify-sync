<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core\Event;

interface EventManagerInterface {
    public function dispatch(string $event, array $data = []): void;
    public function addListener(string $event, callable $listener, int $priority = 10): void;
    public function removeListener(string $event, callable $listener): void;
}
