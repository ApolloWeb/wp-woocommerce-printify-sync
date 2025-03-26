<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use eftec\bladeone\BladeOne;

class BladeTemplateEngine {
    private $blade;
    private $cache_path;

    public function __construct() {
        $this->cache_path = WPWPS_PATH . 'cache';
        
        // Create cache directory if it doesn't exist
        if (!file_exists($this->cache_path)) {
            wp_mkdir_p($this->cache_path);
        }

        // Use MODE_DEBUG in development, MODE_AUTO in production
        $mode = defined('WP_DEBUG') && WP_DEBUG ? BladeOne::MODE_DEBUG : BladeOne::MODE_AUTO;

        $this->blade = new BladeOne(
            WPWPS_PATH . 'templates',
            $this->cache_path,
            $mode
        );

        // Add WordPress translation function
        $this->blade->directive('__', function($expression) {
            return "<?php echo esc_html__($expression, 'wp-woocommerce-printify-sync'); ?>";
        });
    }

    public function render(string $view, array $data = []): string {
        try {
            return $this->blade->run($view, $data);
        } catch (\Exception $e) {
            // Log error and return error message
            error_log('Template Error: ' . $e->getMessage());
            return sprintf(
                '<div class="error"><p>%s</p></div>',
                esc_html__('Error loading template.', 'wp-woocommerce-printify-sync')
            );
        }
    }
}