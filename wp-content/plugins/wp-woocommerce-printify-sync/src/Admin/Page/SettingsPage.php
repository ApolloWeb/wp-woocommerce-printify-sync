<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Page;

use ApolloWeb\WPWooCommercePrintifySync\Service\{
    ApiKeyManager,
    SettingsService
};

class SettingsPage extends AbstractAdminPage
{
    private ApiKeyManager $apiKeyManager;
    private SettingsService $settings;

    public function __construct(
        ApiKeyManager $apiKeyManager,
        SettingsService $settings
    ) {
        $this->apiKeyManager = $apiKeyManager;
        $this->settings = $settings;
    }

    public function getTitle(): string
    {
        return __('Settings', 'wp-woocommerce-printify-sync');
    }

    public function getMenuTitle(): string
    {
        return __('Settings', 'wp-woocommerce-printify-sync');
    }

    public function getCapability(): string
    {
        return 'manage_options';
    }

    public function getMenuSlug(): string
    {
        return 'wpwps-settings';
    }

    public function register(): void
    {
        add_submenu_page(
            'wpwps-dashboard',
            $this->getTitle(),
            $this->getMenuTitle(),
            $this->getCapability(),
            $this->getMenuSlug(),
            [$this, 'render']
        );

        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerSettings(): void
    {
        register_setting('wpwps_settings', 'wpwps_printify_api_key');
        register_setting('wpwps_settings', 'wpwps_geolocation_api_key');
        register_setting('wpwps_settings', 'wpwps_currency_api_key');
        register_setting('wpwps_settings', 'wpwps_google_drive_credentials');
        register_setting('wpwps_settings', 'wpwps_cloudflare_r2_credentials');
        
        // API Settings Section
        add_settings_section(
            'wpwps_api_settings',
            __('API Settings', 'wp-woocommerce-printify-sync'),
            [$this, 'renderApiSettingsSection'],
            'wpwps_settings'
        );

        // Add settings fields
        add_settings_field(
            'wpwps_printify_api_key',
            __('Printify API Key', 'wp-woocommerce-printify-sync'),
            [$this, 'renderApiKeyField'],
            'wpwps_settings',
            'wpwps_api_settings',
            ['key' => 'printify']
        );

        // Add other API keys similarly...
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_style(
            'wpwps-settings',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/css/settings.css',
            ['wpwps-admin-core'],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-settings',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/js/settings.js',
            ['jquery', 'wpwps-admin-core'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-settings', 'wpwpsSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_settings'),
            'i18n' => [
                'testSuccess' => __('API connection successful!', 'wp-woocommerce-printify-sync'),
                'testError' => __('API connection failed:', 'wp-woocommerce-printify-sync')
            ]
        ]);
    }

    public function render(): void
    {
        if (!current_user_can($this->getCapability())) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $this->renderTemplate('settings', [
            'settings' => $this->settings->all(),
            'apiStatus' => $this->apiKeyManager->checkAllApiConnections()
        ]);
    }
}