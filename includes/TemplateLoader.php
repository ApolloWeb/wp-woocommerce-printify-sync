<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Template loader for WP WooCommerce Printify Sync
 *
 * Handles loading and rendering template files
 */
class TemplateLoader {
    /**
     * Get template path
     *
     * @param string $template Template name
     * @return string Full template path
     */
    public static function getTemplatePath($template) {
        return plugin_dir_path(dirname(__FILE__)) . 'admin/templates/' . $template . '.php';
    }
    
    /**
     * Render a template with data
     *
     * @param string $template Template name
     * @param array $data Data to pass to template
     * @param bool $echo Whether to echo or return
     * @return string|void Template content
     */
    public static function render($template, $data = [], $echo = true) {
        $file = self::getTemplatePath($template);
        
        if (!file_exists($file)) {
            return;
        }
        
        // Extract data to make it available in template
        extract($data);
        
        if ($echo) {
            // Start output buffering
            ob_start();
            include $file;
            echo ob_get_clean();
        } else {
            // Return template content
            ob_start();
            include $file;
            return ob_get_clean();
        }
    }
}