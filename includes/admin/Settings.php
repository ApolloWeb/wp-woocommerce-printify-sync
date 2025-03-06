<?php
/**
 * Settings page for the plugin
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiHelper;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\LogHelper;

class Settings {
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Register settings
        add_action('admin_init', [$this, 'registerSettings']);
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_wp-woocommerce-printify-sync/wp-woocommerce-printify-sync.php', [$this, 'addSettingsLink']);
    }

    /**
     * Register settings
     */
    public function registerSettings() {
        // API settings section
        add_settings_section(
            'wpwprintifysync_api_settings',
            __('API Settings', 'wp-woocommerce-printify-sync'),
            null,
            'wpwprintifysync-settings'
        );

        register_setting('wpwprintifysync-settings', 'wpwprintifysync_api_mode');
        register_setting('wpwprintifysync-settings', 'wpwprintifysync_printify_api_key');
    }

    /**
     * Render settings page
     */
    public function renderSettingsPage() {
        echo '<h1>' . __('Settings', 'wp-woocommerce-printify-sync') . '</h1>';
    }
    
    /**
     * Add settings link
     */
    public function addSettingsLink($links) {
        $settings_link = '<a href="admin.php?page=wpwprintifysync-settings">' . __('Settings', 'wp-woocommerce-printify-sync') . '</a>';
        array_push($links, $settings_link);
        return $links;
    }
}