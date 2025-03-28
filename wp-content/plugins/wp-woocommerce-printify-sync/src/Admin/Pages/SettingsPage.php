<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

class SettingsPage
{
    /**
     * Render the settings page.
     *
     * @return void
     */
    public function render(): void
    {
        // Fetch settings from the database
        $settings = get_option('wpwps_settings', []);

        // Fetch available shops if API key is set
        $shops = [];
        if (!empty($settings['printify_api_key'])) {
            try {
                $client = new \ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyClient($settings['printify_api_key']);
                $shops = $client->getShops();
            } catch (\Exception $e) {
                // Handle API errors gracefully
                $shops = [];
            }
        }

        // Render the settings page template
        echo View::render('wpwps-settings', [
            'settings' => $settings,
            'shops' => $shops,
        ]);
    }
}