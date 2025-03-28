<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

abstract class ServiceProvider {
    protected array $config = [];

    abstract public function register(): void;

    protected function loadConfig(string $key): array {
        $configFile = WPWPS_PATH . "config/{$key}.php";
        return file_exists($configFile) ? require $configFile : [];
    }

    protected function loadView(string $view, array $data = []): string {
        static $blade = null;
        
        if ($blade === null) {
            $views = WPWPS_PATH . 'templates';
            $cache = WPWPS_PATH . 'templates/cache';
            $blade = new \eftec\bladeone\BladeOne($views, $cache, \eftec\bladeone\BladeOne::MODE_DEBUG);
        }

        return $blade->run($view, $data);
    }
}