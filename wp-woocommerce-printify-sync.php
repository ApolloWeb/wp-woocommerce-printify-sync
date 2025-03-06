<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: WordPress plugin to provide syncing between WooCommerce and Printify.
 * Version: 1.0.0
 * Author: ApolloWeb
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('WPWPRINTIFYSYNC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPWPRINTIFYSYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/admin/Menu.php';
require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/MenuHelper.php';

use ApolloWeb\WPWooCommercePrintifySync\Helpers\MenuHelper;

MenuHelper::init();