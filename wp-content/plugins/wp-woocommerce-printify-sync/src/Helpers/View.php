<?php
/**
 * View Helper
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

/**
 * BladeOne wrapper for template rendering
 */
class View
{
    /**
     * BladeOne instance
     *
     * @var \eftec\bladeone\BladeOne
     */
    protected static $blade;

    /**
     * Initialize BladeOne
     *
     * @return \eftec\bladeone\BladeOne
     */
    protected static function initBlade()
    {
        if (is_null(self::$blade)) {
            // Include BladeOne manually since we can't use Composer
            require_once WPWPS_PLUGIN_DIR . 'lib/BladeOne/BladeOne.php';
            
            // Create BladeOne instance
            self::$blade = new \eftec\bladeone\BladeOne(
                WPWPS_TEMPLATES_DIR,
                WPWPS_TEMPLATES_DIR . 'cache',
                \eftec\bladeone\BladeOne::MODE_AUTO
            );
            
            // Define custom directives
            self::defineCustomDirectives();
        }
        
        return self::$blade;
    }

    /**
     * Define custom directives
     *
     * @return void
     */
    protected static function defineCustomDirectives()
    {
        $blade = self::$blade;
        
        // WordPress directive for translation
        $blade->directive('__', function ($expression) {
            return "<?php echo esc_html__({$expression}, 'wp-woocommerce-printify-sync'); ?>";
        });
        
        // WordPress directive for translation with escaping
        $blade->directive('_e', function ($expression) {
            return "<?php echo _e({$expression}, 'wp-woocommerce-printify-sync'); ?>";
        });
        
        // WordPress directive for admin URL
        $blade->directive('admin_url', function ($expression) {
            return "<?php echo esc_url(admin_url({$expression})); ?>";
        });
        
        // WordPress directive for AJAX URL
        $blade->directive('ajax_url', function () {
            return "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
        });
        
        // WordPress directive for nonce field
        $blade->directive('nonce_field', function ($expression) {
            return "<?php wp_nonce_field({$expression}); ?>";
        });
        
        // WordPress directive for assets URL
        $blade->directive('asset', function ($expression) {
            return "<?php echo esc_url(WPWPS_ASSETS_URL . {$expression}); ?>";
        });
    }

    /**
     * Render a template
     *
     * @param string $template Template name
     * @param array  $data     Data to pass to the template
     * @param bool   $echo     Whether to echo or return the template
     * @return string|void Template content if $echo is false
     */
    public static function render($template, $data = [], $echo = true)
    {
        // Initialize BladeOne
        $blade = self::initBlade();
        
        // Add plugin URL to data
        $data['plugin_url'] = WPWPS_PLUGIN_URL;
        $data['assets_url'] = WPWPS_ASSETS_URL;
        
        try {
            // Render template
            $content = $blade->run($template, $data);
            
            if ($echo) {
                echo $content;
                return;
            }
            
            return $content;
        } catch (\Exception $e) {
            // Log error if template rendering fails
            error_log('Template rendering error: ' . $e->getMessage());
            
            if (WP_DEBUG) {
                echo '<div class="notice notice-error"><p>Template Error: ' . esc_html($e->getMessage()) . '</p></div>';
            }
        }
    }
}