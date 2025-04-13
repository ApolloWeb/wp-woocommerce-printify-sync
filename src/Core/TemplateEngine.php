<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Template Engine Class
 */
class TemplateEngine {
    /**
     * @var array Template data
     */
    private $data = [];
    
    /**
     * Render a template
     * 
     * @param string $template Template name
     * @param array $data Data to pass to the template
     * @return void
     */
    public function render($template, $data = []) {
        $this->data = $data;
        $template_path = WPWPS_PLUGIN_DIR . 'templates/' . $template . '.php';
        
        if (file_exists($template_path)) {
            include WPWPS_PLUGIN_DIR . 'templates/wpwps-layout.php';
        } else {
            // Fallback message if template doesn't exist
            echo '<div class="error"><p>' . 
                 sprintf(
                     esc_html__('Template %s not found.', 'wp-woocommerce-printify-sync'),
                     esc_html($template)
                 ) . 
                 '</p></div>';
        }
    }
    
    /**
     * Include a partial template
     * 
     * @param string $partial Partial template name
     * @param array $data Data to pass to the partial
     * @return void
     */
    public function partial($partial, $data = []) {
        // Merge data with current data
        $merged_data = array_merge($this->data, $data);
        $this->data = $merged_data;
        
        $partial_path = WPWPS_PLUGIN_DIR . 'templates/partials/' . $partial . '.php';
        
        if (file_exists($partial_path)) {
            include $partial_path;
        } else {
            // Fallback message if partial doesn't exist
            echo '<div class="error"><p>' . 
                 sprintf(
                     esc_html__('Partial %s not found.', 'wp-woocommerce-printify-sync'),
                     esc_html($partial)
                 ) . 
                 '</p></div>';
        }
    }
    
    /**
     * Get data from the template
     * 
     * @param string $key Data key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get($key, $default = null) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
    
    /**
     * Set data for the template
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
    }
    
    /**
     * Check if data exists
     * 
     * @param string $key Data key
     * @return boolean
     */
    public function has($key) {
        return isset($this->data[$key]);
    }
    
    /**
     * Get content for the template
     * 
     * @return string
     */
    public function getContent() {
        ob_start();
        $template_path = WPWPS_PLUGIN_DIR . 'templates/' . $this->get('template') . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error"><p>' . 
                 sprintf(
                     esc_html__('Template %s not found.', 'wp-woocommerce-printify-sync'),
                     esc_html($this->get('template'))
                 ) . 
                 '</p></div>';
        }
        
        return ob_get_clean();
    }
}
