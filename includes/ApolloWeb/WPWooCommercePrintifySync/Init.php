<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class Init {
    public static function register_services() {
        // List of classes to initialize
        $services = [
            Enqueue::class,
            Admin\Menu::class,
        ];

        foreach ($services as $service) {
            self::instantiate($service);
        }
    }

    private static function instantiate($class) {
        $service = new $class();
        if (method_exists($service, 'register')) {
            $service->register();
        }
    }
}