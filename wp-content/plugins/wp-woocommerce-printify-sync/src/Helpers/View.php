<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class View {
    public static function render(string $view, array $data = []): string {
        static $blade = null;
        
        if ($blade === null) {
            $views = WPWPS_PATH . 'templates';
            $cache = WPWPS_PATH . 'templates/cache';
            $blade = new \eftec\bladeone\BladeOne($views, $cache, \eftec\bladeone\BladeOne::MODE_DEBUG);
            
            // Add custom directives
            $blade->directive('wpnonce', function ($expression) {
                return "<?php wp_nonce_field({$expression}); ?>";
            });
        }

        return $blade->run($view, $data);
    }

    public static function asset(string $path): string {
        return WPWPS_URL . 'assets/' . ltrim($path, '/');
    }

    public static function escape(string $content): string {
        return esc_html($content);
    }
}