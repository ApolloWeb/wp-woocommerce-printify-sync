<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

class PrintifySettingsSection extends AbstractSettingsSection
{
    public function __construct()
    {
        $this->sectionId = 'printify';
        $this->sectionTitle = __('Printify API Settings', 'wp-woocommerce-printify-sync');
    }

    public function registerSettings(): void
    {
        register_setting('wpwps_settings', 'wpwps_printify_api_key');
        register_setting('wpwps_settings', 'wpwps_printify_endpoint');
    }

    public function renderSection(): void
    {
        require WPWPS_PLUGIN_DIR . 'templates/admin/Settings/Sections/PrintifySettings.php';
    }

    public function testConnection(): array
    {
        $apiKey = get_option('wpwps_printify_api_key');
        $endpoint = get_option('wpwps_printify_endpoint');

        $response = wp_remote_get($endpoint . '/shops', [
            'headers' => ['Authorization' => 'Bearer ' . $apiKey]
        ]);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        return [
            'success' => true,
            'message' => __('Printify API connection successful!', 'wp-woocommerce-printify-sync')
        ];
    }

    public function validateSettings(array $settings): array
    {
        $errors = [];
        
        if (empty($settings['wpwps_printify_api_key'])) {
            $errors[] = __('Printify API key is required', 'wp-woocommerce-printify-sync');
        }

        if (empty($settings['wpwps_printify_endpoint'])) {
            $errors[] = __('Printify API endpoint is required', 'wp-woocommerce-printify-sync');
        }

        return $errors;
    }
}