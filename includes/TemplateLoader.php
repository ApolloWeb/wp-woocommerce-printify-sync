<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * Template Loader
 * 
 * Handles loading of templates with proper prefixing
 */
class TemplateLoader {
    /**
     * Get template path
     *
     * @param string $template_name Template name (without prefix)
     * @param string $subfolder Optional subfolder
     * @return string Full path to template
     */
    public function get_template_path($template_name, $subfolder = '') {
        $prefix = WPWPS_ASSET_PREFIX;
        $template_name = $prefix . $template_name;
        
        $subfolder = !empty($subfolder) ? trailingslashit($subfolder) : '';
        $path = WPWPS_TEMPLATES_PATH . $subfolder . $template_name;
        
        // Allow theme overrides
        $theme_path = get_template_directory() . '/wp-woocommerce-printify-sync/' . $subfolder . $template_name;
        
        if (file_exists($theme_path)) {
            return $theme_path;
        }
        
        return $path;
    }
    
    /**
     * Load template
     *
     * @param string $template_name Template name (without prefix)
     * @param array $args Arguments to pass to template
     * @param string $subfolder Optional subfolder
     * @return void
     */
    public function load_template($template_name, $args = [], $subfolder = '') {
        $path = $this->get_template_path($template_name, $subfolder);
        
        if (!file_exists($path)) {
            return;
        }
        
        extract($args);
        include $path;
    }
}
