<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class View {
    /**
     * Render a template
     *
     * @param string $template Template path
     * @param array $data Template data
     * @return string Rendered template
     */
    public static function render(string $template, array $data = []): string 
    {
        $template_path = WPWPS_PLUGIN_PATH . 'templates/' . $template . '.php';
        
        if (!file_exists($template_path)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        // Extract data to make it available in template
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include template file
        include $template_path;
        
        // Get contents and clean buffer
        return ob_get_clean();
    }
}
