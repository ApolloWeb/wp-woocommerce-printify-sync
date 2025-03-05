<?php
/**
 * Admin Logs Viewer Page Template - Main File
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\LogHelper;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LogViewer;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LogExporter;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LogCleaner;

// Get filter parameters
$log_level = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$context = isset($_GET['context']) ? sanitize_text_field($_GET['context']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Export logs if requested
if (isset($_POST['wpwprintifysync_export_logs'])) {
    check_admin_referer('wpwprintifysync_export_logs', 'wpwprintifysync_export_nonce');
    LogExporter::getInstance()->exportLogs($log_level, $date_from, $date_to, $context, $search);
}

// Clean logs if requested
if (isset($_POST['wpwprintifysync_clean_logs'])) {
    check_admin_referer('wpwprintif