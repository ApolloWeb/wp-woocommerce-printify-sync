<?php
/**
 * Main plugin class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu;
use ApolloWeb\WPWooCommercePrintifySync\Admin\DashboardWidgets;
use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApi;
use ApolloWeb\WPWooCommercePrintifySync\Core\Scheduler;
use ApolloWeb\WPWooCommercePrintifySync\Core\HookManager;
use ApolloWeb\WPWooCommercePrintifySync\Core\Assets;

/**
 * Class Plugin
 */
class Plugin {
    /**
     * Singleton instance
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Container for service instances
     *
     * @var array
     */
    private $services = [];

    /**
     * Plugin initialization
     *
     * @return void
     */
    public function init() {
        // Initialize services
        $this->initServices();
        
        // Load textdomain
        add_action('init', [$this, 'loadTextdomain']);
        
        // Register hooks
        $this->getService('hookManager')->registerHooks();
    }

    /**
     * Initialize plugin services
     *
     * @return void
     */
    private function initServices() {
        // Core services
        $this->services['assets'] = new Assets();
        $this->services['hookManager'] = new HookManager($this);
        $this->services['printifyApi'] = new PrintifyApi();
        $this->services['scheduler'] = new Scheduler();
        
        // Admin services
        if (is_admin()) {
            $this->services['adminMenu'] = new AdminMenu($this);
            $this->services['dashboardWidgets'] = new DashboardWidgets($this);
        }
        
        // Initialize services that need to be started
        foreach ($this->services as $service) {
            if (method_exists($service, 'init')) {
                $service->init();
            }
        }
    }

    /**
     * Get a service
     *
     * @param string $service Service name
     * @return mixed Service instance
     */
    public function getService($service) {
        if (isset($this->services[$service])) {
            return $this->services[$service];
        }
        
        return null;
    }

    /**
     * Load plugin text domain
     *
     * @return void
     */
    public function loadTextdomain() {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(WPWPS_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Get singleton instance
     *
     * @return Plugin Singleton instance
     */
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
}
