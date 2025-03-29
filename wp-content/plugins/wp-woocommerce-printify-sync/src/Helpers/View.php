<?php
/**
 * View Helper Class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

// We'll use BladeOne for templating
use eftec\bladeone\BladeOne;

/**
 * View helper class for rendering templates
 */
class View
{
    /**
     * BladeOne instance
     *
     * @var BladeOne
     */
    protected $blade;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Set up BladeOne
        $views = WPWPS_PLUGIN_DIR . 'templates';
        $cache = WPWPS_PLUGIN_DIR . 'templates/cache';
        
        // Create cache directory if it doesn't exist
        if (!file_exists($cache)) {
            wp_mkdir_p($cache);
        }
        
        // Initialize BladeOne with default settings
        // MODE_DEBUG for development, MODE_AUTO for production
        $mode = WP_DEBUG ? BladeOne::MODE_DEBUG : BladeOne::MODE_AUTO;
        $this->blade = new BladeOne($views, $cache, $mode);
        
        // Add custom directives
        $this->addCustomDirectives();
    }
    
    /**
     * Render a template
     *
     * @param string $template The template name
     * @param array $data Data to pass to the template
     * @return string
     */
    public function render($template, $data = [])
    {
        // Add some default data
        $data = array_merge([
            'plugin_url' => WPWPS_PLUGIN_URL,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_nonce'),
        ], $data);
        
        try {
            return $this->blade->run($template, $data);
        } catch (\Exception $e) {
            // Log the error
            error_log($e->getMessage());
            
            // Return error message if WP_DEBUG is enabled
            if (WP_DEBUG) {
                return '<div class="error"><p>Template Error: ' . $e->getMessage() . '</p></div>';
            }
            
            // Return a generic error message
            return '<div class="error"><p>' . __('An error occurred while rendering the template.', WPWPS_TEXT_DOMAIN) . '</p></div>';
        }
    }
    
    /**
     * Add custom directives to Blade
     *
     * @return void
     */
    protected function addCustomDirectives()
    {
        // Add WordPress translation directive
        $this->blade->directive('__', function($expression) {
            return "<?php echo __({$expression}, WPWPS_TEXT_DOMAIN); ?>";
        });
        
        // Add WordPress e-translation directive
        $this->blade->directive('e__', function($expression) {
            return "<?php echo esc_html__({$expression}, WPWPS_TEXT_DOMAIN); ?>";
        });
        
        // Add WordPress escape directive
        $this->blade->directive('esc', function($expression) {
            return "<?php echo esc_html({$expression}); ?>";
        });
        
        // Add WordPress admin URL directive
        $this->blade->directive('adminUrl', function($expression) {
            return "<?php echo admin_url({$expression}); ?>";
        });
        
        // Add nonce field directive
        $this->blade->directive('wpNonce', function($expression) {
            return "<?php wp_nonce_field({$expression}); ?>";
        });
    }
}