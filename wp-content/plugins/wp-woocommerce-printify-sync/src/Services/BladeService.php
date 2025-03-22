<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class BladeService {
    private $views_path;
    private $cache_path;

    public function __construct($views_path = null, $cache_path = null) {
        $this->views_path = $views_path ?: WPWPS_PLUGIN_DIR . 'resources/views';
        $this->cache_path = $cache_path ?: WPWPS_PLUGIN_DIR . 'cache';
    }

    public function render($view, $data = []) {
        $file = $this->views_path . '/' . str_replace('.', '/', $view) . '.blade.php';
        
        if (!file_exists($file)) {
            throw new \Exception("View file not found: {$file}");
        }

        extract($data);
        ob_start();
        include $file;
        return ob_get_clean();
    }
}
