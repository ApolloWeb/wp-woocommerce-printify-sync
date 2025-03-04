<?php
/**
 * @deprecated 1.0.8 Use ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsPage instead
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class SettingsPage
 * 
 * @deprecated 1.0.8 Use ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsPage instead
 */
class SettingsPage {
    /**
     * Constructor
     */
    public function __construct() {
        _deprecated_file(
            __FILE__,
            '1.0.8',
            'includes/Settings/SettingsPage.php',
            'This file is deprecated. Please use ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsPage instead.'
        );
    }

    /**
     * Render the settings page
     * 
     * @deprecated 1.0.8 Use ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsPage::render() instead
     */
    public function render() {
        // Forward to the proper settings page class
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsPage')) {
            $settings = new \ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsPage();
            if (method_exists($settings, 'render')) {
                $settings->render();
                return;
            }
        }

        // Fallback if the proper class isn't available
        echo '<div class="wrap">';
        echo '<h1><i class="fas fa-cog"></i> Settings</h1>';
        echo '<div class="notice notice-warning"><p><strong>Notice:</strong> This settings page is deprecated. Please update your code.</p></div>';
        echo '<p>Configure plugin settings here.</p>';
        echo '</div>';
    }
}