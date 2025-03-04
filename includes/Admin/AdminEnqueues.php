<?php
/**
 * Admin Enqueues
 *
 * @package WP_WooCommerce_Printify_Sync
 */

namespace WpWoocommercePrintifySync\Admin;

use WpWoocommercePrintifySync\Helpers\EnqueueHelper;
use WpWoocommercePrintifySync\Helpers\AssetHelper;

defined('ABSPATH') || exit;

/**
 * Class for handling admin enqueues.
 */
class AdminEnqueues {

    /**
     * Initialize the class.
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (!$this->is_plugin_page($hook)) {
            return;
        }

        // Master stylesheet - high priority (50) to override WordPress admin styles
        EnqueueHelper::enqueue_style('admin-master', 'admin/dashboard-master.css', array(), false, 50);

        // Continue loading specific styles for backward compatibility
        // But give them lower priority (10) so master stylesheet takes precedence
        EnqueueHelper::enqueue_style('admin-common', 'admin-common.css', array(), false, 10);
        EnqueueHelper::enqueue_style('admin-navigation', 'admin-navigation.css', array(), false, 10);
        
        // Dashboard-specific styles
        if (strpos($hook, 'toplevel_page_printify-sync') !== false) {
            EnqueueHelper::enqueue_style('admin-dashboard', 'admin-dashboard.css', array(), false, 10);
            EnqueueHelper::enqueue_style('admin-dashboard-tables', 'admin-dashboard-tables.css', array(), false, 10);
        }

        // Page-specific styles
        if (strpos($hook, 'printify-sync_page_printify-sync-settings') !== false) {
            EnqueueHelper::enqueue_style('settings', 'admin/settings-page.css', array(), false, 10);
        } elseif (strpos($hook, 'printify-sync_page_printify-sync-shops') !== false) {
            EnqueueHelper::enqueue_style('shops', 'admin/shops-page.css', array(), false, 10);
        } elseif (strpos($hook, 'printify-sync_page_printify-sync-exchange-rates') !== false) {
            EnqueueHelper::enqueue_style('exchange-rates', 'admin/exchange-rates-page.css', array(), false, 10);
        } elseif (strpos($hook, 'printify-sync_page_printify-sync-postman') !== false) {
            EnqueueHelper::enqueue_style('postman', 'admin/postman-page.css', array(), false, 10);
        } elseif (strpos($hook, 'printify-sync_page_printify-sync-products') !== false && isset($_GET['action']) && $_GET['action'] === 'import') {
            EnqueueHelper::enqueue_style('products-import', 'admin/products-import.css', array(), false, 10);
        }

        // Widget styles
        EnqueueHelper::enqueue_style('admin-widgets', 'admin-widgets.css', array(), false, 10);

        // Enqueue scripts
        $this->enqueue_admin_scripts($hook);
        
        // Add admin notice for stylesheet debugging if in development mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p>Printify Sync: Using consolidated admin stylesheet. If you see styling issues, check browser console for conflicts.</p>';
                echo '</div>';
            });
        }
    }

    /**
     * Check if current page is a plugin page.
     *
     * @param string $hook Current admin page hook.
     * @return bool Whether it's a plugin page.
     */