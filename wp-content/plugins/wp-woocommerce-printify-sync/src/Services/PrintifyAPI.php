<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\APIClientInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Exceptions\APIException;

class PrintifyAPI extends AbstractService
{
    private APIClientInterface $client;
    private string $shopId;
    private array $cache = [];

    public function __construct(
        APIClientInterface $client,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        parent::__construct($logger, $config);
        $this->client = $client;
        $this->shopId = $this->config->get('printify_shop_id');
    }

    public function getShops(): array
    {
        return $this->request('GET', 'shops');
    }

    public function getProducts(array $params = []): array
    {
        return $this->request('GET', "shops/{$this->shopId}/products", $params);
    }

    public function getProduct(string $productId): array
    {
        return $this->request('GET', "shops/{$this->shopId}/products/{$productId}");
    }

    public function createProduct(array $data): array
    {
        return $this->request('POST', "shops/{$this->shopId}/products", $data);
    }

    public function updateProduct(string $productId, array $data): array
    {
        return $this->request('PUT', "shops/{$this->shopId}/products/{$productId}", $data);
    }

    public function publishProduct(string $productId): array
    {
        return $this->request(
            'POST',
            "shops/{$this->shopId}/products/{$productId}/publish"
        );
    }

    public function getOrders(array $params = []): array
    {
        return $this->request('GET', "shops/{$this->shopId}/orders", $params);
    }

    public function getOrder(string $orderId): array
    {
        return $this->request('GET', "shops/{$this->shopId}/orders/{$orderId}");
    }

    public function createOrder(array $data): array
    {
        return $this->request('POST', "shops/{$this->shopId}/orders", $data);
    }

    public function cancelOrder(string $orderId): array
    {
        return $this->request(
            'POST',
            "shops/{$this->shopId}/orders/{$orderId}/cancel"
        );
    }

    public function getShipping(array $params = []): array
    {
        return $this->request(
            'GET',
            "shops/{$this->shopId}/shipping-profiles",
            $params
        );
    }

    public function getBlueprints(): array
    {
        if (!isset($this->cache['blueprints'])) {
            $this->cache['blueprints'] = $this->request('GET', 'catalog/blueprints');
        }
        return $this->cache['blueprints'];
    }

    public function getBlueprintVariants(string $blueprintId): array
    {
        $cacheKey = "blueprint_variants_{$blueprintId}";
        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->request(
                'GET',
                "catalog/blueprints/{$blueprintId}/variants"
            );
        }
        return $this->cache[$cacheKey];
    }

    public function getProviders(string $blueprintId): array
    {
        $cacheKey = "providers_{$blueprintId}";
        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->request(
                'GET',
                "catalog/blueprints/{$blueprintId}/print-providers"
            );
        }
        return $this->cache[$cacheKey];
    }

    public function getShippingCosts(string $providerId, array $address): array
    {
        return $this->request(
            'POST',
            "shipping/calculate",
            [
                'print_provider_id' => $providerId,
                'address' => $address
            ]
        );
    }

    public function uploadImage(string $url): array
    {
        return $this->request('POST', 'uploads/images', ['url' => $url]);
    }

    public function createWebhook(string $url, array $events): array
    {
        return $this->request('POST', "shops/{$this->shopId}/webhooks", [
            'url' => $url,
            'events' => $events
        ]);
    }

    public function deleteWebhook(string $webhookId): array
    {
        return $this->request(
            'DELETE',
            "shops/{$this->shopId}/webhooks/{$webhookId}"
        );
    }

    private function request(
        string $method,
        string $endpoint,
        array $data = []
    ): array {
        try {
            $response = match ($method) {
                'GET' => $this->client->get($endpoint, $data),
                'POST' => $this->client->post($endpoint, $data),
                'PUT' => $this->client->put($endpoint, $data),
                'DELETE' => $this->client->delete($endpoint),
                default => throw new \InvalidArgumentException("Invalid method: {$method}")
            };

            $this->logOperation('request', [
                'method' => $method,
                'endpoint' => $endpoint,
                'data' => $data,
                'response' => $response
            ]);

            return $response;

        } catch (APIException $e) {
            $this->logError('request', $e, [
                'method' => $method,
                'endpoint' => $endpoint,
                'data' => $data
            ]);
            throw $e;
        }
    }
}