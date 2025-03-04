<?php
/**
 * Asset Loading Handler
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utilities
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utilities;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class EnqueueAssets
 */
class EnqueueAssets {

    /**
     * Singleton instance
     *
     * @var EnqueueAssets
     */
    private static $instance = null;

    /**
     * Current screen ID
     *
     * @var string
     */
    private $current_screen = '';

    /**
     * Registered styles
     *
     * @var array
     */
    private $styles = [];

    /**
     * Registered scripts
     *
     * @var array
     */
    private $scripts = [];
    
    /**
     * Asset prefix to avoid conflicts
     *
     * @var string
     */
    private $prefix = 'printify-sync-';

    /**
     * Get the singleton instance
     *
     * @return EnqueueAssets
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register the singleton instance
     *
     * @return EnqueueAssets
     */
    public static function register() {
        return self::get_instance();
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Register styles and scripts
        add_action('admin_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Debug info to footer for development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_footer', [$this, 'debug_assets_info']);
        }
    }

    /**
     * Register all assets
     *
     * @param string $hook Current admin page hook
     */
    public function register_assets($hook) {
        // Store current screen for later use
        $this->current_screen = $hook;
        
        // Register shared styles
        $this->register_style('dashboard', 'css/admin-dashboard.css');
        $this->register_style('tables', 'css/admin-dashboard-tables.css');
        $this->register_style('widgets', 'css/admin-widgets.css');
        
        // Register page-specific styles
        $this->register_style('postman', 'css/admin/postman-page.css');
        $this->register_style('products', 'css/admin/products-import.css');
        $this->register_style('settings', 'css/admin/settings-page.css');
        $this->register_style('shops', 'css/admin/shops-page.css');
        $this->register_style('exchange-rates', 'css/admin/exchange-rates-page.css');

        // Register shared scripts
        $this->register_script('dashboard', 'js/admin-dashboard.js', ['jquery']);
        $this->register_script('widgets', 'js/admin-widgets.js', ['jquery']);
        
        // Register page-specific scripts
        $this->register_script('postman', 'js/admin/postman-page.js', ['jquery']);
        $this->register_script('products', 'js/admin/products-import.js', ['jquery']);
        $this->register_script('settings', 'js/admin/settings-page.js', ['jquery']);
        $this->register_script('shops', 'js/admin/shops-page.js', ['jquery']);
        $this->register_script('exchange-rates', 'js/admin/exchange-rates-page.js', ['jquery']);
    }

    /**
     * Enqueue assets based on current screen
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        // Always load these styles on our plugin pages
        if ($this->is_plugin_page($hook)) {
            $this->enqueue_style('dashboard');
            $this->enqueue_style('tables');
            $this->enqueue_style('widgets');
            $this->enqueue_script('dashboard');
            $this->enqueue_script('widgets');
            
            // Add core localization
            wp_localize_script($this->get_handle('dashboard'), 'printify_dashboard', [
                'nonce' => wp_create_nonce('printify_dashboard_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'plugin_url' => PRINTIFY_SYNC_URL,
                'current_time' => function_exists('printify_sync_get_current_datetime') ? 
                    printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s'),
                'current_user' => function_exists('printify_sync_get_current_user') ? 
                    printify_sync_get_current_user() : 'No user'
            ]);
        }
        
        // Specific page assets
        if (strpos($hook, 'printify-postman') !== false) {
            $this->enqueue_style('postman');
            $this->enqueue_script('postman');
            wp_localize_script($this->get_handle('postman'), 'printify_postman', [
                'nonce' => wp_create_nonce('printify_postman_nonce')
            ]);
        }
        
        if (strpos($hook, 'printify-products') !== false) {
            $this->enqueue_style('products');
            $this->enqueue_script('products');
        }
        
        if (strpos($hook, 'printify-settings') !== false) {
            $this->enqueue_style('settings');
            $this->enqueue_script('settings');
        }
        
        if (strpos($hook, 'printify-shops') !== false) {
            $this->enqueue_style('shops');
            $this->enqueue_script('shops');
        }
        
        if (strpos($hook, 'printify-exchange-rates') !== false) {
            $this->enqueue_style('exchange-rates');
            $this->enqueue_script('exchange-rates');
        }
        
        // Inline CSS with important styles in case of conflicts
        add_action('admin_head', [$this, 'add_critical_css']);
    }

    /**
     * Add critical CSS that should always be applied
     */
    public function add_critical_css() {
        if (!$this->is_plugin_page($this->current_screen)) {
            return;
        }
        
        ?>
        <style type="text/css">
        /* Critical Printify Sync styles that should always apply */
        .printify-sync-dashboard-page {
            max-width: 1200px;
        }
        
        .printify-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .printify-info-box {
            background: #f8f9fa;
            border-left: 4px solid #0073aa;
            padding: 10px 15px;
            margin-bottom: 20px;
            flex: 1;
        }
        </style>
        <?php
    }

    /**
     * Check if current page is a plugin page
     *
     * @param string $hook Current admin page hook
     * @return bool
     */
    private function is_plugin_page($hook) {
        return strpos($hook, 'wp-woocommerce-printify-sync') !== false || 
               strpos($hook, 'printify-') !== false;
    }

    /**
     * Get prefixed handle
     *
     * @param string $handle Original handle
     * @return string
     */
    private function get_handle($handle) {
        return $this->prefix . $handle;
    }

    /**
     * Register a style
     *
     * @param string $handle Style handle
     * @param string $path Path relative to the assets directory
     * @param array $deps Dependencies
     * @param string $version Version
     * @param string $media Media
     */
    private function register_style($handle, $path, $deps = [], $version = null, $media = 'all') {
        // Use our prefixed handle
        $prefixed_handle = $this->get_handle($handle);
        
        if (!$version) {
            $version = defined('PRINTIFY_SYNC_VERSION') ? PRINTIFY_SYNC_VERSION : '1.0';
            // Add timestamp in debug mode for cache busting
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $full_path = PRINTIFY_SYNC_PATH . 'assets/' . $path;
                if (file_exists($full_path)) {
                    $version .= '.' . filemtime($full_path);
                }
            }
        }
        
        $file_path = PRINTIFY_SYNC_PATH . 'assets/' . $path;
        $file_url = PRINTIFY_SYNC_URL . 'assets/' . $path;
        
        // Only register if file exists
        if (file_exists($file_path)) {
            wp_register_style($prefixed_handle, $file_url, $deps, $version, $media);
            $this->styles[$handle] = [
                'handle' => $prefixed_handle,
                'path' => $file_path,
                'url' => $file_url,
                'version' => $version,
                'exists' => true
            ];
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Printify Sync: Style file not found: {$file_path}");
            }
            $this->styles[$handle] = [
                'handle' => $prefixed_handle,
                'path' => $file_path,
                'url' => $file_url,
                'version' => $version,
                'exists' => false
            ];
        }
    }

    /**
     * Register a script
     *
     * @param string $handle Script handle
     * @param string $path Path relative to the assets directory
     * @param array $deps Dependencies
     * @param string $version Version
     * @param bool $in_footer Whether to enqueue in footer
     */
    private function register_script($handle, $path, $deps = [], $version = null, $in_footer = true) {
        // Use our prefixed handle
        $prefixed_handle = $this->get_handle($handle);
        
        if (!$version) {
            $version = defined('PRINTIFY_SYNC_VERSION') ? PRINTIFY_SYNC_VERSION : '1.0';
            // Add timestamp in debug mode for cache busting
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $full_path = PRINTIFY_SYNC_PATH . 'assets/' . $path;
                if (file_exists($full_path)) {
                    $version .= '.' . filemtime($full_path);
                }
            }
        }
        
        $file_path = PRINTIFY_SYNC_PATH . 'assets/' . $path;
        $file_url = PRINTIFY_SYNC_URL . 'assets/' . $path;
        
        // Only register if file exists
        if (file_exists($file_path)) {
            wp_register_script($prefixed_handle, $file_url, $deps, $version, $in_footer);
            $this->scripts[$handle] = [
                'handle' => $prefixed_handle,
                'path' => $file_path,
                'url' => $file_url,
                'version' => $version,
                'exists' => true
            ];
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Printify Sync: Script file not found: {$file_path}");
            }
            $this->scripts[$handle] = [
                'handle' => $prefixed_handle,
                'path' => $file_path,
                'url' => $file_url,
                'version' => $version,
                'exists' => false
            ];
        }
    }
    
    /**
     * Enqueue a registered style
     *
     * @param string $handle Style handle
     */
    public function enqueue_style($handle) {
        if (isset($this->styles[$handle]) && $this->styles[$handle]['exists']) {
            wp_enqueue_style($this->styles[$handle]['handle']);
        }
    }
    
    /**
     * Enqueue a registered script
     *
     * @param string $handle Script handle
     */
    public function enqueue_script($handle) {
        if (isset($this->scripts[$handle]) && $this->scripts[$handle]['exists']) {
            wp_enqueue_script($this->scripts[$handle]['handle']);
        }
    }
    
    /**
     * Debug assets in footer
     */
    public function debug_assets_info() {
        if (!$this->is_plugin_page($this->current_screen) || !current_user_can('manage_options')) {
            return;
        }
        
        echo '<div style="margin-top: 30px; padding: 10px; background: #f8f9fa; border-left: 4px solid #0073aa; font-family: monospace;">';
        echo '<strong>📋 Printify Sync Asset Loading Debug:</strong><br>';
        echo 'Current Screen: ' . esc_html($this->current_screen) . '<br>';
        
        echo '<h4>Plugin Path Information:</h4>';
        echo 'PRINTIFY_SYNC_PATH: ' . esc_html(PRINTIFY_SYNC_PATH) . '<br>';
        echo 'PRINTIFY_SYNC_URL: ' . esc_html(PRINTIFY_SYNC_URL) . '<br>';
        echo 'Handle Prefix: ' . esc_html($this->prefix) . '<br>';
        
        echo '<h4>Registered Styles:</h4>';
        echo '<ul style="margin-top: 5px;">';
        foreach ($this->styles as $name => $info) {
            $status = $info['exists'] ? '✅' : '❌';
            echo '<li>';
            echo $status . ' ' . esc_html($name) . ' (handle: ' . esc_html($info['handle']) . '): ';
            echo esc_html($info['path']);
            if (!$info['exists']) {
                echo ' <strong style="color: red;">File not found!</strong>';
            }
            echo '</li>';
        }
        echo '</ul>';
        
        echo '<h4>Registered Scripts:</h4>';
        echo '<ul style="margin-top: 5px;">';
        foreach ($this->scripts as $name => $info) {
            $status = $info['exists'] ? '✅' : '❌';
            echo '<li>';
            echo $status . ' ' . esc_html($name) . ' (handle: ' . esc_html($info['handle']) . '): ';
            echo esc_html($info['path']);
            if (!$info['exists']) {
                echo ' <strong style="color: red;">File not found!</strong>';
            }
            echo '</li>';
        }
        echo '</ul>';
        
        // Display all enqueued styles and scripts for this page
        echo '<h4>Currently Enqueued Styles:</h4>';
        global $wp_styles;
        echo '<ul style="margin-top: 5px; max-height: 150px; overflow-y: auto;">';
        foreach ($wp_styles->queue as $handle) {
            $is_ours = strpos($handle, $this->prefix) === 0;
            echo '<li>';
            if ($is_ours) {
                echo '✅ ';
            }
            echo esc_html($handle);
            echo ' - ' . esc_html($wp_styles->registered[$handle]->src);
            echo '</li>';
        }
        echo '</ul>';
        
        echo '<h4>Currently Enqueued Scripts:</h4>';
        global $wp_scripts;
        echo '<ul style="margin-top: 5px; max-height: 150px; overflow-y: auto;">';
        foreach ($wp_scripts->queue as $handle) {
            $is_ours = strpos($handle, $this->prefix) === 0;
            echo '<li>';
            if ($is_ours) {
                echo '✅ ';
            }
            echo esc_html($handle);
            echo ' - ' . esc_html($wp_scripts->registered[$handle]->src);
            echo '</li>';
        }
        echo '</ul>';
        
        echo '<h4>Debug Information:</h4>';
        echo 'Current Date/Time: ' . esc_html(function_exists('printify_sync_get_current_datetime') ? printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s')) . '<br>';
        echo 'Current User: ' . esc_html(function_exists('printify_sync_get_current_user') ? printify_sync_get_current_user() : 'Unknown') . '<br>';
        
        echo '</div>';
    }
}