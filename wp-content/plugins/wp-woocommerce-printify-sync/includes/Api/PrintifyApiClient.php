<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Api;

/**
 * Printify API Client
 */
class PrintifyApiClient {
    
    /**
     * Get orders from Printify
     *
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Orders response
     */
    public function getOrders(int $page = 1, int $limit = 20): array {
        $params = [
            'page' => $page,
            'limit' => $limit
        ];
        
        return $this->request('GET', "shops/{$this->shop_id}/orders.json", $params);
    }
    
    /**
     * Get a specific order from Printify
     *
     * @param string $order_id Printify order ID
     * @return array Order data
     */
    public function getOrder(string $order_id): array {
        return $this->request('GET', "shops/{$this->shop_id}/orders/{$order_id}.json");
    }
    
    /**
     * Create an order on Printify
     *
     * @param array $order_data Order data
     * @return array Created order
     */
    public function createOrder(array $order_data): array {
        return $this->request('POST', "shops/{$this->shop_id}/orders.json", [], $order_data);
    }
    
    /**
     * Cancel an order on Printify
     *
     * @param string $order_id Printify order ID
     * @return array Cancel response
     */
    public function cancelOrder(string $order_id): array {
        return $this->request('POST', "shops/{$this->shop_id}/orders/{$order_id}/cancel.json");
    }
    
    /**
     * Get shipments for an order
     *
     * @param string $order_id Printify order ID
     * @return array Shipments data
     */
    public function getShipments(string $order_id): array {
        return $this->request('GET', "shops/{$this->shop_id}/orders/{$order_id}/shipments.json");
    }
    
    /**
     * Create a test webhook endpoint
     * 
     * @param string $url Webhook URL
     * @param string $secret Webhook secret
     * @return array Response
     */
    public function createWebhook(string $url, string $secret): array {
        $data = [
            'url' => $url,
            'secret' => $secret,
            'events' => [
                'order.created',
                'order.updated',
                'shipment.created',
                'product.created',
                'product.updated'
            ]
        ];
        
        return $this->request('POST', "shops/{$this->shop_id}/webhooks.json", [], $data);
    }
    
    /**
     * Get all print providers
     *
     * @return array Print providers
     */
    public function getPrintProviders(): array {
        return $this->request('GET', 'print-providers.json');
    }
    
    /**
     * Get print provider details
     *
     * @param string $provider_id Provider ID
     * @return array Provider details
     */
    public function getPrintProvider(string $provider_id): array {
        return $this->request('GET', "print-providers/{$provider_id}.json");
    }
    
    /**
     * Get shipping profiles for a provider
     *
     * @param string $provider_id Provider ID
     * @return array Shipping profiles
     */
    public function getProviderShippingProfiles(string $provider_id): array {
        return $this->request('GET', "print-providers/{$provider_id}/shipping.json");
    }
    
    /**
     * Get shipping rates for a specific order
     *
     * @param string $provider_id Provider ID
     * @param array $address Shipping address
     * @param array $items Line items
     * @return array Shipping rates
     */
    public function getShippingRates(string $provider_id, array $address, array $items): array {
        $data = [
            'provider_id' => $provider_id,
            'address' => $address,
            'line_items' => $items
        ];
        
        return $this->request('POST', "shipping/rates.json", [], $data);
    }
}
