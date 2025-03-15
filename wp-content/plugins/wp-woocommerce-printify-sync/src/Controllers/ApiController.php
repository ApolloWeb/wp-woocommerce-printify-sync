<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Controllers;

class ApiController
{
    private string $currentTime;
    private string $currentUser;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:43:33';
        $this->currentUser = 'ApolloWeb';

        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route('wpwps/v1', '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'getProducts'],
            'permission_callback' => [$this, 'checkPermission'],
        ]);

        register_rest_route('wpwps/v1', '/sync', [
            'methods' => 'POST',
            'callback' => [$this, 'triggerSync'],
            'permission_callback' => [$this, 'checkPermission'],
        ]);
    }

    public function checkPermission(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    public function getProducts(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $repository = new \ApolloWeb\WPWooCommercePrintifySync\Models\ProductRepository();
            $products = $repository->getAll();
            return new \WP_REST_Response($products, 200);
        } catch (\Exception $e) {
            return new \WP_REST_Response(['error' => $e->getMessage()], 500);
        }
    }

    public function triggerSync(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $syncManager = new \ApolloWeb\WPWooCommercePrintifySync\Services\SyncManager();
            $result = $syncManager->syncProducts($request['shop_id'] ?? '');
            return new \WP_REST_Response($result, 200);
        } catch (\Exception $e) {
            return new \WP_REST_Response(['error' => $e->getMessage()], 500);
        }
    }
}