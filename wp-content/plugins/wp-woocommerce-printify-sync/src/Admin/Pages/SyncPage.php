<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

class SyncPage
{
    /**
     * Render the sync page.
     *
     * @return void
     */
    public function render(): void
    {
        // Fetch settings from the database
        $settings = get_option('wpwps_settings', []);

        // Render the sync page template
        echo View::render('wpwps-products', [
            'settings' => $settings
        ]);
    }
}