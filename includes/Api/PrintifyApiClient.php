<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Api;

class PrintifyApiClient {
    private $base_url = 'https://api.printify.com/v1';
    
    // Shop endpoints
    public function getShops(): ?array {
        return $this->request('GET', '/shops.json');
    }

    // Catalog endpoints
    public function getCatalog(): ?array {
        return $this->request('GET', '/catalog/blueprints.json');
    }

    public function getBlueprint(string $blueprint_id): ?array {
        return $this->request('GET', "/catalog/blueprints/{$blueprint_id}.json");
    }

    public function getProviders(string $blueprint_id): ?array {
        return $this->request('GET', "/catalog/blueprints/{$blueprint_id}/print_providers.json");
    }

    // Extended Catalog Methods
    public function getBlueprints(array $params = []): ?array {
        return $this->request('GET', '/catalog/blueprints.json', $params);
    }

    public function getBlueprintVariants(string $blueprint_id, string $provider_id): ?array {
        return $this->request('GET', "/catalog/blueprints/{$blueprint_id}/print_providers/{$provider_id}/variants.json");
    }

    public function getBlueprintPrintAreas(string $blueprint_id): ?array {
        return $this->request('GET', "/catalog/blueprints/{$blueprint_id}/print_areas.json");
    }

    // Product endpoints
    public function createProduct(string $shop_id, array $data): ?array {
        return $this->request('POST', "/shops/{$shop_id}/products.json", [], $data);
    }

    public function updateProduct(string $shop_id, string $product_id, array $data): ?array {
        return $this->request('PUT', "/shops/{$shop_id}/products/{$product_id}.json", [], $data);
    }

    public function publishProduct(string $shop_id, string $product_id): ?array {
        return $this->request('POST', "/shops/{$shop_id}/products/{$product_id}/publish.json");
    }

    // Order endpoints
    public function createOrder(string $shop_id, array $data): ?array {
        return $this->request('POST', "/shops/{$shop_id}/orders.json", [], $data);
    }

    public function calculateShipping(string $shop_id, array $data): ?array {
        return $this->request('POST', "/shops/{$shop_id}/orders/shipping.json", [], $data);
    }

    public function cancelOrder(string $shop_id, string $order_id): ?array {
        return $this->request('POST', "/shops/{$shop_id}/orders/{$order_id}/cancel.json");
    }

    // Extended Order Methods
    public function submitOrder(string $shop_id, string $order_id): ?array {
        return $this->request('POST', "/shops/{$shop_id}/orders/{$order_id}/submit.json");
    }

    public function calculateShippingRates(string $shop_id, array $address_data): ?array {
        return $this->request('POST', "/shops/{$shop_id}/orders/shipping/calculate.json", [], $address_data);
    }

    public function getOrderStatuses(string $shop_id, array $order_ids): ?array {
        return $this->request('POST', "/shops/{$shop_id}/orders/status.json", [], ['orders' => $order_ids]);
    }

    // Webhook endpoints
    public function createWebhook(string $shop_id, string $url): ?array {
        return $this->request('POST', "/shops/{$shop_id}/webhooks.json", [], [
            'url' => $url,
            'events' => [
                'order.created',
                'order.updated',
                'order.canceled',
                'order.failed',
                'shipping.update',
                'product.published',
                'product.updated',
                'variant.updated'
            ]
        ]);
    }

    // Extended Webhook Methods 
    public function getWebhooks(string $shop_id): ?array {
        return $this->request('GET', "/shops/{$shop_id}/webhooks.json");
    }

    public function deleteWebhook(string $shop_id, string $webhook_id): void {
        $this->request('DELETE', "/shops/{$shop_id}/webhooks/{$webhook_id}.json");
    }

    // Extended Upload Methods
    public function uploadArtwork(string $file_path): ?array {
        $boundary = wp_generate_password(24);
        $headers = array_merge($this->getHeaders(), [
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary
        ]);

        return $this->request('POST', '/uploads/images.json', [], [
            'file' => new \CURLFile($file_path)
        ], $headers);
    }

    // ...existing request method code...
}
