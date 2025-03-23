<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Api;

class RestController {
    private $namespace = 'wpwps/v1';

    public function init(): void {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void {
        register_rest_route($this->namespace, '/webhooks', [
            'methods' => 'POST',
            'callback' => [$this, 'handleWebhook'],
            'permission_callback' => [$this, 'validateWebhook']
        ]);

        register_rest_route($this->namespace, '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'getStatus'],
            'permission_callback' => '__return_true'
        ]);
    }
}
