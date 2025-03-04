<?php
/**
 * Helper functions for Printify Sync plugin
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper function to get current user login
 * 
 * @return string Current user login
 */
function printify_sync_get_current_user() {
    $current_user = wp_get_current_user();
    return $current_user->user_login;
}

/**
 * Helper function to get current datetime in UTC
 * 
 * @return string Current datetime in YYYY-MM-DD HH:MM:SS format
 */
function printify_sync_get_current_datetime() {
    return gmdate('Y-m-d H:i:s');
}

/**
 * Check if we're in development mode
 *
 * @return bool True if in development mode
 */
function printify_sync_is_development_mode() {
    $environment = get_option('printify_sync_environment', 'production');
    return ($environment === 'development' || (defined('WP_DEBUG') && WP_DEBUG));
}