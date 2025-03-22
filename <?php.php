<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Synchronize WooCommerce with Printify
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Start output buffering early
if (!defined('DOING_AJAX') && !defined('DOING_CRON')) {
    ob_start();
}

// Register shutdown function to handle output buffer
register_shutdown_function(function() {
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
});
