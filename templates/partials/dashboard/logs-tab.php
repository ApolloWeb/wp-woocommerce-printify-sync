<?php
/**
 * Logs & Debugging tab for WooCommerce Printify Sync
 *
 * @package WP_Woocommerce_Printify_Sync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get log data with pagination
$page = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
$per_page = 15;

// Get current filter settings
$log_type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : '';
$search_term = isset($_GET['log_search']) ? sanitize_text_field($_GET['log_search']) : '';
$date_start = isset($_GET['date_start']) ? sanitize_text_field($_GET['date_start']) : '';
$date_end = isset($_GET['date