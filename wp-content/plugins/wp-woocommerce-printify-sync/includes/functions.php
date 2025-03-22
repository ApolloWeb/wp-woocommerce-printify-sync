<?php
/**
 * Helper functions for the plugin
 */

if (!function_exists('wpps_get_template')) {
    function wpps_get_template($template, $data = []) {
        $file = WPPS_TEMPLATES_PATH . $template . '.php';
        if (file_exists($file)) {
            extract($data);
            ob_start();
            include $file;
            return ob_get_clean();
        }
        return '';
    }
}

if (!function_exists('wpps_render_template')) {
    function wpps_render_template($template, $data = []) {
        echo wpps_get_template($template, $data);
    }
}
