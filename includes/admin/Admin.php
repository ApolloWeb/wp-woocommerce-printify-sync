<?php

namespace ApolloWeb\WPWoocomercePrintifySync;

use ApolloWeb\WPWoocomercePrintifySync\PrintifyAPI;

class Admin {
    public static function register_menu() {
        add_menu_page(
            __( 'Printify Sync', 'wwps' ),
            __( 'Printify Sync', 'wwps' ),
            'manage_options',
            'wwps-settings',
            array( __CLASS__, 'settings_page' ),
            'dashicons-update',
            56
        );

        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    public static function settings_page() {
        // Fetch shops from Printify API.
        $api = new PrintifyAPI();
        $shops = $api->get_shops();

        // Include the template file.
        include WWPS_PLUGIN_DIR . 'includes/templates/settings-page.php';
    }

    public static function register_settings() {
        register_setting( 'wwps-settings-group', 'wwps_printify_api_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'wwps-settings-group', 'wwps_printify_api_endpoint', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
    }

    public static function enqueue_scripts() {
        wp_enqueue_script( 'wwps-shops', WWPS_PLUGIN_URL . 'assets/js/shops.js', array( 'jquery' ), '1.0.0', true );
        wp_enqueue_style( 'wwps-admin-styles', WWPS_PLUGIN_URL . 'assets/css/admin-styles.css', array(), '1.0.0' );
    }
}