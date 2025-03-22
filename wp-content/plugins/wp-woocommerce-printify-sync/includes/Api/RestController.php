<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

class RestController {
    private const NAMESPACE = 'wpps/v1';

    public function init(): void {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'getSettings'],
            'permission_callback' => [$this, 'checkPermission']
        ]);

        register_rest_route(self::NAMESPACE, '/settings', [
            'methods' => 'POST',
            'callback' => [$this, 'updateSettings'],
            'permission_callback' => [$this, 'checkPermission']
        ]);
    }

    public function checkPermission(): bool {
        return current_user_can('manage_woocommerce');
    }
}
