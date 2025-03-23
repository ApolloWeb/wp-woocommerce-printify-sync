<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class PrintifyApi {
    private $settings;
    private $api_url = 'https://api.printify.com/v1/';
    private $timeout = 30;

    public function __construct(Settings $settings) {
        $this->settings = $settings;
    }

    // Shops
    public function getShops(): array {
        return $this->request('shops.json');
    }

    // Products
    public function getProducts(int $shop_id, array $params = []): array {
        return $this->request("shops/{$shop_id}/products.json", [
            'method' => 'GET',
            'query' => $params
        ]);
    }

    public function createProduct(int $shop_id, array $product_data): array {
        return $this->request("shops/{$shop_id}/products.json", [
            'method' => 'POST',
            'body' => json_encode($product_data)
        ]);
    }

    public function publishProduct(int $shop_id, string $product_id): array {
        return $this->request("shops/{$shop_id}/products/{$product_id}/publish.json", [
            'method' => 'POST'
        ]);
    }

    // Orders
    public function createOrder(int $shop_id, array $order_data): array {
        return $this->request("shops/{$shop_id}/orders.json", [
            'method' => 'POST',
            'body' => json_encode($order_data)
        ]);
    }

    public function getOrder(int $shop_id, string $order_id): array {
        return $this->request("shops/{$shop_id}/orders/{$order_id}.json");
    }

    public function cancelOrder(int $shop_id, string $order_id): array {
        return $this->request("shops/{$shop_id}/orders/{$order_id}/cancel.json", [
            'method' => 'POST'
        ]);
    }

    // Shipping
    public function calculateShipping(int $shop_id, array $shipping_data): array {
        return $this->request("shops/{$shop_id}/orders/shipping.json", [
            'method' => 'POST',
            'body' => json_encode($shipping_data)
        ]);
    }

    public function getShippingRates(int $shop_id, array $shipping_data): array {
        return $this->request("shops/{$shop_id}/orders/shipping/calculate.json", [
            'method' => 'POST',
            'body' => json_encode($shipping_data)
        ]);
    }

    public function updateShippingProfile(int $shop_id, array $profile_data): array {
        return $this->request("shops/{$shop_id}/shipping.json", [
            'method' => 'PUT',
            'body' => json_encode($profile_data)
        ]);
    }

    // Catalog Methods
    public function getBlueprints(array $params = []): array {
        return $this->request('catalog/blueprints.json', [
            'query' => array_merge([
                'limit' => 100,
                'page' => 1
            ], $params)
        ]);
    }

    public function getBlueprintPrintProviders(string $blueprint_id): array {
        return $this->request("catalog/blueprints/{$blueprint_id}/print_providers.json");
    }

    public function getBlueprintVariants(string $blueprint_id, string $print_provider_id): array {
        return $this->request("catalog/blueprints/{$blueprint_id}/print_providers/{$print_provider_id}/variants.json");
    }

    public function getBlueprintShippingInfo(string $blueprint_id, string $print_provider_id): array {
        return $this->request("catalog/blueprints/{$blueprint_id}/print_providers/{$print_provider_id}/shipping.json");
    }

    // Print Provider Methods
    public function getPrintProviders(): array {
        return $this->request('catalog/print_providers.json');
    }

    public function getPrintProviderShippingInfo(string $print_provider_id): array {
        return $this->request("catalog/print_providers/{$print_provider_id}/shipping.json");
    }

    public function getPrintProviderShipping(int $provider_id): array {
        return $this->request("print-providers/{$provider_id}/shipping.json");
    }

    // Image Uploads
    public function uploadImage(string $image_url): array {
        return $this->request('uploads/images.json', [
            'method' => 'POST',
            'body' => json_encode([
                'url' => $image_url
            ])
        ]);
    }

    // Webhook Methods
    public function createWebhook(int $shop_id, string $url, array $topics): array {
        return $this->request("shops/{$shop_id}/webhooks.json", [
            'method' => 'POST',
            'body' => json_encode([
                'url' => $url,
                'topics' => $topics,
                'secret' => wp_generate_password(32, true, true)
            ])
        ]);
    }

    protected function handleApiError(array $response): void {
        if (isset($response['error'])) {
            $message = $response['error']['message'] ?? 'Unknown API error';
            $code = $response['error']['code'] ?? 500;
            throw new \Exception($message, $code);
        }
    }

    private function request(string $endpoint, array $args = []): array {
        $defaults = [
            'method' => 'GET',
        ];

        $args = array_merge($defaults, $args);
        $url = $this->api_url . $endpoint;

        $response = wp_remote_request($url, [
            'method' => $args['method'],
            'timeout' => $this->timeout,
            'body' => $args['body'] ?? null,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings->getApiKey(),
                'Content-Type' => 'application/json'
            ],
            'query' => $args['query'] ?? []
        ]);

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $this->handleApiError($data);

        return $data;
    }

    // Print Providers
    public function getPrintProviders(): array {
        return $this->request('print-providers.json');
    }

    public function getPrintProviderProducts(int $provider_id): array {
        return $this->request("print-providers/{$provider_id}/products.json");
    }

    public function getPrintProviderShipping(int $provider_id): array {
        return $this->request("print-providers/{$provider_id}/shipping.json");
    }

    // Catalog
    public function getCatalogProducts(array $params = []): array {
        return $this->request('catalog/blueprints.json', ['query' => $params]);
    }

    public function getCatalogProduct(string $blueprint_id): array {
        return $this->request("catalog/blueprints/{$blueprint_id}.json");
    }

    public function getCatalogVariants(string $blueprint_id): array {
        return $this->request("catalog/blueprints/{$blueprint_id}/variants.json");
    }

    // Uploads
    public function uploadImage(string $image_url): array {
        return $this->request('uploads/images.json', [
            'method' => 'POST',
            'body' => json_encode(['url' => $image_url])
        ]);
    }

    // Webhooks
    public function createWebhook(int $shop_id, array $webhook_data): array {
        return $this->request("shops/{$shop_id}/webhooks.json", [
            'method' => 'POST',
            'body' => json_encode($webhook_data)
        ]);
    }

    public function deleteWebhook(int $shop_id, string $webhook_id): void {
        $this->request("shops/{$shop_id}/webhooks/{$webhook_id}.json", [
            'method' => 'DELETE'
        ]);
    }

    // Shop Products
    public function publishProductToShop(int $shop_id, string $product_id, array $publish_data): array {
        return $this->request("shops/{$shop_id}/products/{$product_id}/publish.json", [
            'method' => 'POST',
            'body' => json_encode($publish_data)
        ]);
    }

    public function disconnectProductFromShop(int $shop_id, string $product_id): void {
        $this->request("shops/{$shop_id}/products/{$product_id}/disconnect.json", [
            'method' => 'POST'
        ]);
    }

    // Orders
    public function submitOrder(int $shop_id, string $order_id): array {
        return $this->request("shops/{$shop_id}/orders/{$order_id}/submit.json", [
            'method' => 'POST'
        ]);
    }

    public function calculateOrderShipping(int $shop_id, array $address_data): array {
        return $this->request("shops/{$shop_id}/orders/shipping.json", [
            'method' => 'POST',
            'body' => json_encode($address_data)
        ]);
    }

    // Blueprints
    public function getBlueprint(string $blueprint_id): array {
        return $this->request("catalog/blueprints/{$blueprint_id}.json");
    }

    public function getBlueprintPrintAreas(string $blueprint_id): array {
        return $this->request("catalog/blueprints/{$blueprint_id}/print-areas.json");
    }

    public function getBlueprintProviders(string $blueprint_id): array {
        return $this->request("catalog/blueprints/{$blueprint_id}/print-providers.json");
    }

    // Variants
    public function getBlueprintVariants(string $blueprint_id, int $provider_id): array {
        return $this->request("catalog/blueprints/{$blueprint_id}/print-providers/{$provider_id}/variants.json");
    }

    public function getVariantShipping(string $blueprint_id, int $provider_id, array $variant_ids): array {
        return $this->request("catalog/blueprints/{$blueprint_id}/print-providers/{$provider_id}/shipping.json", [
            'method' => 'POST',
            'body' => json_encode(['variant_ids' => $variant_ids])
        ]);
    }

    // Mockups
    public function generateMockup(int $shop_id, string $product_id): array {
        return $this->request("shops/{$shop_id}/products/{$product_id}/mockup.json", [
            'method' => 'POST'
        ]);
    }

    public function getProductionCosts(int $shop_id, string $product_id): array {
        return $this->request("shops/{$shop_id}/products/{$product_id}/costs.json");
    }

    // Image Handling
    public function uploadArtwork(string $image_path): array {
        $file_data = file_get_contents($image_path);
        $boundary = wp_generate_password(24);
        
        return $this->request('uploads/images.json', [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary
            ],
            'body' => $this->buildMultipartBody($boundary, [
                'file' => [
                    'contents' => $file_data,
                    'filename' => basename($image_path)
                ]
            ])
        ]);
    }

    private function buildMultipartBody(string $boundary, array $fields): string {
        $body = '';
        foreach ($fields as $name => $data) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$data['filename']}\"\r\n";
            $body .= "Content-Type: application/octet-stream\r\n\r\n";
            $body .= $data['contents'] . "\r\n";
        }
        $body .= "--{$boundary}--\r\n";
        return $body;
    }

    // Price Calculations
    public function calculateProductPrices(int $shop_id, array $products): array {
        return $this->request("shops/{$shop_id}/products/calculate-prices.json", [
            'method' => 'POST',
            'body' => json_encode(['products' => $products])
        ]);
    }

    // Batch Operations
    public function batchCreateProducts(int $shop_id, array $products): array {
        return $this->request("shops/{$shop_id}/products/batch.json", [
            'method' => 'POST',
            'body' => json_encode(['products' => $products])
        ]);
    }

    public function batchPublishProducts(int $shop_id, array $product_ids): array {
        return $this->request("shops/{$shop_id}/products/publish-batch.json", [
            'method' => 'POST',
            'body' => json_encode(['products' => $product_ids])
        ]);
    }

    // Metadata
    public function updateProductMetadata(int $shop_id, string $product_id, array $metadata): array {
        return $this->request("shops/{$shop_id}/products/{$product_id}/metadata.json", [
            'method' => 'PUT',
            'body' => json_encode($metadata)
        ]);
    }

    public function getProductMetadata(int $shop_id, string $product_id): array {
        return $this->request("shops/{$shop_id}/products/{$product_id}/metadata.json");
    }
}