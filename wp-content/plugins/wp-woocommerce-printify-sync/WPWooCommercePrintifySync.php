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

// Simple autoloader based on file naming and namespacing
spl_autoload_register( function( $class ) {
	$prefix   = __NAMESPACE__ . '\\';
	$base_dir = __DIR__ . '/includes/';
	$len      = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}
	$relative_class = substr( $class, $len );
	$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
	if ( file_exists( $file ) ) {
		require $file;
	}
} );

// Initialize plugin components
function init() {
	// ...existing initialization code...
	if ( is_admin() ) {
		( new Admin() )->init();
		( new AjaxHandler() )->init();
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
