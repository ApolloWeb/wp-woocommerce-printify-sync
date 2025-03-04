<?php
/**
 * Helper functions
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Debug logging
 *
 * @param mixed $message Message to log
 */
function printify_sync_debug($message) {
    if (!defined('PRINTIFY_SYNC_DEBUG') || !PRINTIFY_SYNC_DEBUG) {
        return;
    }
    
    if (is_array($message) || is_object($message)) {
        error_log(print_r($message, true));
    } else {
        error_log($message);
    }
}

/**
 * Get current user's login name
 *
 * @return string User login or 'No user'
 */
function get_current_user() {
    $user = wp_get_current_user();
    return !empty($user->user_login) ? $user->user_login : 'No user';
}

/**
 * Get formatted current datetime
 *
 * @return string Formatted date/time in YYYY-MM-DD HH:MM:SS format
 */
function get_current_datetime() {
    return gmdate('Y-m-d H:i:s');
}

/**
 * Locate