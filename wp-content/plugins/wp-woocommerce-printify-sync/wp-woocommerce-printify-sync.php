<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products from Printify to WooCommerce
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Version: 1.0.0
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

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

namespace ApolloWeb\WPWooCommercePrintifySync;

// Define plugin constants
define( 'APOLLOWEB_PRINTIFY_VERSION', '1.0.0' );
define( 'APOLLOWEB_PRINTIFY_FILE', __FILE__ );
define( 'APOLLOWEB_PRINTIFY_PATH', plugin_dir_path( __FILE__ ) );
define( 'APOLLOWEB_PRINTIFY_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLOWEB_PRINTIFY_BASENAME', plugin_basename( __FILE__ ) );
define( 'APOLLOWEB_PRINTIFY_LAST_UPDATED', '2025-03-09 11:36:11' );

// Determine the path to the Composer autoloader
// First check for project-level autoloader (johnpbloch/wordpress structure)
$autoloader_paths = [
    // Project root (outside WordPress directory)
    realpath( ABSPATH . '/../vendor/autoload.php' ),
    // WordPress root
    realpath( ABSPATH . '/vendor/autoload.php' ),
    // Plugin directory
    dirname( __FILE__ ) . '/vendor/autoload.php',
];

$autoloader_loaded = false;
foreach ( $autoloader_paths as $autoloader_path ) {
    if ( file_exists( $autoloader_path ) ) {
        require_once $autoloader_path;
        $autoloader_loaded = true;
        break;
    }
}

// If no Composer autoloader found, use our custom autoloader
if ( ! $autoloader_loaded ) {
    require_once APOLLOWEB_PRINTIFY_PATH . 'includes/Utils/Autoloader.php';
    Utils\Autoloader::register();
}

/**
 * Plugin initialization class
 */
final class Plugin {

    /**
     * Instance of this class
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Get the singleton instance of this class
     *
     * @return Plugin
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->defineConstants();
        $this->checkDependencies();
        $this->includeFiles();
        $this->initHooks();
    }

    /**
     * Define additional constants
     *
     * @return void
     */
    private function defineConstants() {
        define( 'APOLLOWEB_PRINTIFY_ADMIN_PATH', APOLLOWEB_PRINTIFY_PATH . 'includes/Admin/' );
        define( 'APOLLOWEB_PRINTIFY_TEMPLATES_PATH', APOLLOWEB_PRINTIFY_PATH . 'views/' );
        define( 'APOLLOWEB_PRINTIFY_ASSETS_URL', APOLLOWEB_PRINTIFY_URL . 'assets/' );
    }

    /**
     * Check for required dependencies
     *
     * @return void
     */
    private function checkDependencies() {
        // Check for required PHP version
        if ( version_compare( PHP_VERSION, '7.3', '<' ) ) {
            add_action( 'admin_notices', [ $this, 'phpVersionNotice' ] );
            return;
        }

        // Check for WooCommerce
        add_action( 'admin_init', [ $this, 'checkWooCommerce' ] );
    }

    /**
     * Display notice for PHP version requirements
     *
     * @return void
     */
    public function phpVersionNotice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php _e( 'WP WooCommerce Printify Sync requires PHP 7.3 or higher.', 'wp-woocommerce-printify-sync' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Check if WooCommerce is active
     *
     * @return void
     */
    public function checkWooCommerce() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', [ $this, 'wooCommerceNotice' ] );
            // Deactivate the plugin
            deactivate_plugins( plugin_basename( __FILE__ ) );
            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }
        }
    }

    /**
     * Display notice for WooCommerce dependency
     *
     * @return void
     */
    public function wooCommerceNotice() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php _e( 'WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Include required files
     *
     * @return void
     */
    private function includeFiles() {
        // No need to include multiple files here - the autoloader will handle it
        require_once APOLLOWEB_PRINTIFY_PATH . 'includes/Core/Container.php';
    }

    /**
     * Initialize hooks
     *
     * @return void
     */
    private function initHooks() {
        // Register activation hook
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        
        // Register deactivation hook
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

        // Add plugin action links
        add_filter( 'plugin_action_links_' . APOLLOWEB_PRINTIFY_BASENAME, [ $this, 'pluginActionLinks' ] );
        
        // Initialize the plugin after WordPress is fully loaded
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init() {
        // Initialize DI container and bootstrap plugin
        $container = new Core\Container();
        $container->bootstrap();

        // Load text domain
        $this->loadTextDomain();

        // Fire 'apolloweb_printify_init' action
        do_action( 'apolloweb_printify_init' );
    }

    /**
     * Load plugin text domain
     *
     * @return void
     */
    public function loadTextDomain() {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    /**
     * Activation hook callback
     *
     * @return void
     */
    public function activate() {
        // Include the installer class
        require_once APOLLOWEB_PRINTIFY_PATH . 'includes/Core/Installer.php';
        $installer = new Core\Installer();
        $installer->run();

        // Add option to redirect to setup wizard on activation
        if ( ! get_option( 'apolloweb_printify_setup_wizard_ran' ) ) {
            set_transient( 'apolloweb_printify_setup_wizard_redirect', true, 30 );
        }
    }

    /**
     * Deactivation hook callback
     *
     * @return void
     */
    public function deactivate() {
        // Include the installer class
        require_once APOLLOWEB_PRINTIFY_PATH . 'includes/Core/Installer.php';
        $installer = new Core\Installer();
        $installer->deactivate();
    }

    /**
     * Add plugin action links
     *
     * @param array $links Default plugin action links
     * @return array Modified plugin action links
     */
    public function pluginActionLinks( $links ) {
        $plugin_links = [
            '<a href="' . admin_url( 'admin.php?page=wp-woocommerce-printify-sync' ) . '">' . __( 'Settings', 'wp-woocommerce-printify-sync' ) . '</a>',
            '<a href="https://github.com/ApolloWeb/wp-woocommerce-printify-sync/wiki" target="_blank">' . __( 'Documentation', 'wp-woocommerce-printify-sync' ) . '</a>',
        ];

        return array_merge( $plugin_links, $links );
    }

    /**
     * Prevent cloning
     *
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cloning is not allowed.', 'wp-woocommerce-printify-sync' ), APOLLOWEB_PRINTIFY_VERSION );
    }

    /**
     * Prevent unserializing
     *
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Unserializing is not allowed.', 'wp-woocommerce-printify-sync' ), APOLLOWEB_PRINTIFY_VERSION );
    }
}

// Initialize the plugin
Plugin::instance();