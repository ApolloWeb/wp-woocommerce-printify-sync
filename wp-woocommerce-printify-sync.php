<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products from Printify to WooCommerce
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Version: 1.2.5
 * Author: ApolloWeb
 * Author URI: https://github.com/ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.3
 * License: MIT
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Define plugin constants
 */
define('PRINTIFY_SYNC_VERSION', '1.2.5');
define('PRINTIFY_SYNC_FILE', __FILE__);
define('PRINTIFY_SYNC_PATH', plugin_dir_path(__FILE__));
define('PRINTIFY_SYNC_URL', plugin_dir_url(__FILE__));
define('PRINTIFY_SYNC_BASENAME', plugin_basename(__FILE__));
define('PRINTIFY_SYNC_DEBUG', true);
define('PRINTIFY_SYNC_DATE', '2025-03-03 13:58:43');
define('PRINTIFY_SYNC_USER', 'ApolloWeb');

/**
 * Include autoloader
 */
require_once PRINTIFY_SYNC_PATH . 'includes/Autoloader.php';

/**
 * Plugin initialization class
 */
final class PrintifySync {
    /**
     * Plugin instance
     * 
     * @var self
     */
    private static $instance = null;
    
    /**
     * Plugin constructor
     */
    private function __construct() {
        // Load the plugin
        add_action('plugins_loaded', [$this, 'init']);
    }
    
    /**
     * Get plugin instance
     * 
     * @return self
     */
    public static function getInstance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize plugin
     * 
     * @return void
     */
    public function init(): void {
        // Register autoloader
        ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();
        
        // Initialize core classes
        $this->initClasses();
        
        // Load textdomain
        add_action('init', [$this, 'loadTextdomain']);
    }
    
    /**
     * Initialize plugin classes
     * 
     * @return void
     */
    private function initClasses(): void {
        // Core functionality
        ApolloWeb\WPWooCommercePrintifySync\Core\Plugin::register();
        ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu::register();
        ApolloWeb\WPWooCommercePrintifySync\Utilities\EnqueueAssets::register();
        
        // Feature classes
        ApolloWeb\WPWooCommercePrintifySync\Admin\AdminDashboard::register();
        ApolloWeb\WPWooCommercePrintifySync\Sync\ProductSync::register();
        ApolloWeb\WPWooCommercePrintifySync\Sync\OrderSync::register();
        ApolloWeb\WPWooCommercePrintifySync\Webhook\WebhookHandler::register();
        ApolloWeb\WPWooCommercePrintifySync\Logs\LogCleanup::register();
        ApolloWeb\WPWooCommercePrintifySync\Settings\NotificationPreferences::register();
        ApolloWeb\WPWooCommercePrintifySync\Settings\EnvironmentSettings::register();
    }
    
    /**
     * Load plugin textdomain
     * 
     * @return void
     */
    public function loadTextdomain(): void {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(PRINTIFY_SYNC_BASENAME) . '/languages'
        );
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing
     */
    public function __wakeup() {}
}

/**
 * Start the plugin
 */
PrintifySync::getInstance();