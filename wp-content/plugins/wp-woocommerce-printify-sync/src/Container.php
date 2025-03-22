<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminManager;
use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyApi;
use ApolloWeb\WPWooCommercePrintifySync\Services\Settings;

class Container {
    private $services = [];

    public function __construct() {
        $this->registerServices();
    }

    private function registerServices(): void {
        $this->services = [
            'settings' => fn() => new Settings(),
            'printify_api' => fn() => new PrintifyApi($this->get('settings')),
            'admin' => fn() => new AdminManager($this)
        ];
    }

    public function get(string $id) {
        if (!isset($this->services[$id])) {
            throw new \InvalidArgumentException("Service $id not found");
        }

        if (is_callable($this->services[$id])) {
            $this->services[$id] = $this->services[$id]();
        }

        return $this->services[$id];
    }
}
