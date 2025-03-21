<?php
/**
 * Template Renderer.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

/**
 * Template Renderer class.
 */
class TemplateRenderer {
    /**
     * Render a template.
     *
     * @param string $template Template name.
     * @param array  $data     Template data.
     * @return void
     */
    public function render($template, $data = []) {
        $template_file = $this->getTemplatePath($template);
        
        if (!file_exists($template_file)) {
            echo esc_html(sprintf(__('Template %s not found.', 'wp-woocommerce-printify-sync'), $template));
            return;
        }
        
        // Extract data to local variables.
        extract($data);
        
        // Start output buffering.
        ob_start();
        
        // Include template file.
        include $template_file;
        
        // Get and clean buffer.
        $output = ob_get_clean();
        
        // Echo output.
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Render a template and return it as a string.
     *
     * @param string $template Template name.
     * @param array  $data     Template data.
     * @return string
     */
    public function renderToString($template, $data = []) {
        $template_file = $this->getTemplatePath($template);
        
        if (!file_exists($template_file)) {
            return sprintf(__('Template %s not found.', 'wp-woocommerce-printify-sync'), $template);
        }
        
        // Extract data to local variables.
        extract($data);
        
        // Start output buffering.
        ob_start();
        
        // Include template file.
        include $template_file;
        
        // Get and clean buffer.
        $output = ob_get_clean();
        
        return $output;
    }

    /**
     * Get template path.
     *
     * @param string $template Template name.
     * @return string
     */
    private function getTemplatePath($template) {
        // Simple template name.
        if (strpos($template, '/') === false) {
            return WPWPS_TEMPLATES_DIR . 'wpwps-' . $template . '.php';
        }
        
        // Template with path.
        return WPWPS_TEMPLATES_DIR . 'partials/' . $template . '/wpwps-' . $template . '.php';
    }

    /**
     * Include a partial.
     *
     * @param string $partial Partial name.
     * @param array  $data    Partial data.
     * @return void
     */
    public function partial($partial, $data = []) {
        $partial_file = WPWPS_TEMPLATES_DIR . 'partials/' . $partial . '.php';
        
        if (!file_exists($partial_file)) {
            echo esc_html(sprintf(__('Partial %s not found.', 'wp-woocommerce-printify-sync'), $partial));
            return;
        }
        
        // Extract data to local variables.
        extract($data);
        
        // Include partial file.
        include $partial_file;
    }

    /**
     * Escape a variable for output.
     *
     * @param mixed  $value Value to escape.
     * @param string $type  Escape type.
     * @return mixed
     */
    public function escape($value, $type = 'html') {
        switch ($type) {
            case 'html':
                return esc_html($value);
            case 'url':
                return esc_url($value);
            case 'attr':
                return esc_attr($value);
            case 'textarea':
                return esc_textarea($value);
            case 'js':
                return esc_js($value);
            case 'raw':
                return $value;
            default:
                return esc_html($value);
        }
    }

    /**
     * Echo an escaped variable.
     *
     * @param mixed  $value Value to escape and echo.
     * @param string $type  Escape type.
     * @return void
     */
    public function e($value, $type = 'html') {
        echo $this->escape($value, $type); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
