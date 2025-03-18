<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminDashboard;
use ApolloWeb\WPWooCommercePrintifySync\Webhook\WebhookHandler;

class Plugin {
    /**
     * Initialize the plugin.
     */
    public function run(): void {
        if (is_admin()) {
            (new AdminDashboard())->register();
        }
        (new WebhookHandler())->register();
        // ...existing initialization code...
    }
    
    // Manual import and settings saving methods remain available as fallback.
}
