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

// Enqueue local Font Awesome
function enqueue_local_font_awesome() {
    wp_enqueue_style( 'font-awesome', plugin_dir_url(__FILE__) . 'assets/css/fontawesome.min.css' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_local_font_awesome' );
add_action( 'admin_enqueue_scripts', 'enqueue_local_font_awesome' );

// Include other necessary plugin files here
require_once plugin_dir_path(__FILE__) . 'src/Admin/Menu/MenuManager.php';

use ApolloWeb\WPWooCommercePrintifySync\Admin\Menu\MenuManager;

// Initialize the menu manager
$menu_manager = new MenuManager();
$menu_manager->initialize();