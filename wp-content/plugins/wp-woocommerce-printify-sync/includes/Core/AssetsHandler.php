<?php
/**
 * Assets handler class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Class AssetsHandler
 */
class AssetsHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'registerAdminAssets']);
        add_action('wp_enqueue_scripts', [$this, 'registerFrontendAssets']);
    }

    /**
     * Register admin assets
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function registerAdminAssets(string $hook): void
    {
        // Only load assets on plugin pages
        if (strpos($hook, 'wpps-') === false) {
            return;
        }

        // Register styles
        wp_register_style(
            'wpps-admin-styles',
            WPPS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WPPS_VERSION
        );

        // Register scripts
        wp_register_script(
            'wpps-admin-script',
            WPPS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            WPPS_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'wpps-admin-script',
            'wppsAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpps-ajax-nonce'),
                'i18n' => [
                    'savingSettings' => __('Saving settings...', 'wp-woocommerce-printify-sync'),
                    'settingsSaved' => __('Settings saved!', 'wp-woocommerce-printify-sync'),
                    'error' => __('Error:', 'wp-woocommerce-printify-sync'),
                ],
            ]
        );

        // Enqueue assets
        wp_enqueue_style('wpps-admin-styles');
        wp_enqueue_script('wpps-admin-script');
    }

    /**
     * Register frontend assets
     *
     * @return void
     */
    public function registerFrontendAssets(): void
    {
        // Only load assets when needed
        if (!is_product() && !is_checkout()) {
            return;
        }

        // Register styles
        wp_register_style(
            'wpps-frontend-styles',
            WPPS_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            WPPS_VERSION
        );

        // Register scripts
        wp_register_script(
            'wpps-frontend-script',
            WPPS_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            WPPS_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'wpps-frontend-script',
            'wppsFrontend',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpps-frontend-nonce'),
            ]
        );

        // Enqueue assets
        wp_enqueue_style('wpps-frontend-styles');
        wp_enqueue_script('wpps-frontend-script');
    }
}