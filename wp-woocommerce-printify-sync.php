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

namespace ApolloWeb\WPWooCommercePrintifySync;

// Define plugin directory
if (!defined('WPWPRINTIFYSYNC_PLUGIN_DIR')) {
    define('WPWPRINTIFYSYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Include the autoloader
require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/Autoloader.php';

// Register the autoloader
$autoloader = new Autoloader();
$autoloader->register();

class WP_WooCommerce_Printify_Sync {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        // Initialization code
    }

    public function get_geolocator() {
        return Geolocation\Geolocator::get_instance();
    }

    public function get_background_processor() {
        return Processing\BackgroundProcessor::get_instance();
    }

    public function get_installer() {
        return Install\Installer::get_instance();
    }
}

// Initialize plugin
function wpwprintifysync_init() {
    return WP_WooCommerce_Printify_Sync::get_instance();
}

// Start the plugin
wpwprintifysync_init();