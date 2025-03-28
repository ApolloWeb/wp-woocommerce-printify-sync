<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

use eftec\bladeone\BladeOne;

class View
{
    /**
     * Render a template with provided data.
     *
     * @param string $template The template name without extension
     * @param array $data Data to pass to the template
     * @return string
     */
    public static function render(string $template, array $data = []): string
    {
        $views = WPWPS_TEMPLATES_DIR;
        $cache = WPWPS_CACHE_DIR;

        // Create cache directory if it doesn't exist
        if (!file_exists($cache)) {
            mkdir($cache, 0755, true);
        }

        try {
            $blade = new BladeOne($views, $cache, BladeOne::MODE_DEBUG);
            return $blade->run($template, $data);
        } catch (\Exception $e) {
            // Log error and return error message
            error_log('WPWPS View Error: ' . $e->getMessage());
            return sprintf(
                '<div class="notice notice-error"><p>%s</p></div>',
                __('Error rendering template. Please check error logs.', 'wp-woocommerce-printify-sync')
            );
        }
    }
}