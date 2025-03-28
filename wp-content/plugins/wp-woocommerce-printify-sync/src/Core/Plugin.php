<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

class Plugin {
    private array $providers = [];
    
    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return Plugin
     */
    public static function getInstance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Boot the plugin
     * 
     * @return void
     */
    public function boot(): void {
        // Initialize third-party libraries
        LibraryLoader::initLibraries();
        
        $this->registerProviders();
        $this->bootProviders();
        $this->initHooks();
    }

    private function registerProviders(): void {
        $this->providers = [
            \ApolloWeb\WPWooCommercePrintifySync\Providers\DashboardProvider::class,
            \ApolloWeb\WPWooCommercePrintifySync\Providers\SettingsProvider::class,
        ];
    }

    private function bootProviders(): void {
        foreach ($this->providers as $providerClass) {
            if (!class_exists($providerClass)) {
                continue;
            }
            
            $provider = new $providerClass();
            if ($provider instanceof ServiceProvider) {
                $provider->register();
            }
        }
    }

    /**
     * Initialize hooks
     */
    private function initHooks(): void {
        add_action('init', [$this, 'loadTextDomain']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Clear BladeOne cache when visiting plugin pages
        add_action('admin_init', function() {
            if (!function_exists('get_current_screen')) {
                return;
            }
            
            $screen = get_current_screen();
            if ($screen && strpos($screen->id, 'wpwps') !== false) {
                // Check for debug mode or force refresh parameter
                if ((defined('WP_DEBUG') && WP_DEBUG) || isset($_GET['refresh_cache'])) {
                    if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Helpers\View')) {
                        \ApolloWeb\WPWooCommercePrintifySync\Helpers\View::clearCache();
                    }
                }
            }
        });
    }
    
    /**
     * Actions to perform on plugin activation
     */
    public static function activate(): void {
        // Create required directories
        self::createRequiredDirectories();
        
        // Clear template cache on activation
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Helpers\View')) {
            \ApolloWeb\WPWooCommercePrintifySync\Helpers\View::clearCache();
        }
    }
    
    /**
     * Actions to perform on plugin deactivation
     */
    public static function deactivate(): void {
        // Any cleanup tasks can go here
    }
    
    /**
     * Create required directories for the plugin
     */
    private static function createRequiredDirectories(): void {
        $directories = [
            WPWPS_PATH . 'templates/cache',
            WPWPS_PATH . 'lib/WpwpsPsr',
            WPWPS_PATH . 'lib/GuzzleHttp/Promise',
        ];
        
        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                wp_mkdir_p($directory);
            }
        }
    }

    public function loadTextDomain(): void {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(plugin_basename(WPWPS_FILE)) . '/languages/'
        );
    }

    public function enqueueAdminAssets(): void {
        if (!function_exists('get_current_screen')) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wpwps') === false) {
            return;
        }

        // Add Google Font: Inter
        wp_enqueue_style(
            'wpwps-google-font-inter', 
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
            [],
            WPWPS_VERSION
        );

        // Core UI Libraries
        wp_enqueue_style('wpwps-bootstrap', WPWPS_URL . 'assets/core/css/bootstrap.min.css', [], WPWPS_VERSION);
        wp_enqueue_style('wpwps-fontawesome', WPWPS_URL . 'assets/core/css/fontawesome.min.css', [], WPWPS_VERSION);
        wp_enqueue_script('wpwps-bootstrap', WPWPS_URL . 'assets/core/js/bootstrap.bundle.min.js', ['jquery'], WPWPS_VERSION, true);
        wp_enqueue_script('wpwps-fontawesome', WPWPS_URL . 'assets/core/js/fontawesome.min.js', [], WPWPS_VERSION, true);
        wp_enqueue_script('wpwps-chartjs', WPWPS_URL . 'assets/core/js/chart.min.js', [], WPWPS_VERSION, true);

        // Custom Dashboard Styles and Scripts
        wp_enqueue_style('wpwps-dashboard-style', WPWPS_URL . 'assets/css/wpwps-dashboard.css', ['wpwps-bootstrap'], WPWPS_VERSION);
        wp_enqueue_script('wpwps-dashboard-script', WPWPS_URL . 'assets/js/wpwps-dashboard.js', ['jquery', 'wpwps-bootstrap', 'wpwps-chartjs'], WPWPS_VERSION, true);
    
        // Add admin body class for proper styling
        add_filter('admin_body_class', function($classes) {
            return $classes . ' wpwps-admin';
        });
    }
}