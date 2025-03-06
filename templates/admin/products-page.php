<?php
/**
 * Admin Product Management Page Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiHelper;

// Get shop ID
$shop_id = get_option('wpwprintifysync_shop_id', 0);

// Handle import request
$import_message = '';
if (isset($_POST['wpwprintifysync_import']) && isset($_POST['product_ids']) && is_array($_POST['product_ids'])) {
    check_admin_referer('wpwprintifysync_import_products', 'wpwprintifysync