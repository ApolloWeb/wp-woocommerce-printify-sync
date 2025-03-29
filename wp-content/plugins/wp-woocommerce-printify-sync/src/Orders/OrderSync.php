<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class OrderSync
{
    private Client $client;
    private string $apiKey;
    private string $shopId;

    public function __construct()
    {
        $this->apiKey = get_option('wpwps_printify_key', '');
        $this->shopId = get_option('wpwps_shop_id', '');
        $this->client = new Client([
            'base_uri' => get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1/'),
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
            ]
        ]);
    }

    public function createPrintifyOrder(\WC_Order $wcOrder): ?string
    {
        try {
            $order = $this->createOrderFromWC($wcOrder);
            $response = $this->client->post(
                "shops/{$this->shopId}/orders.json",
                ['json' => $order->toPrintify()]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            $printifyId = $data['id'] ?? '';

            if ($printifyId) {
                update_post_meta($wcOrder->get_id(), '_printify_order_id', $printifyId);
                $wcOrder->add_order_note('Created Printify order: ' . $printifyId);
                return $printifyId;
            }

            return null;
        } catch (GuzzleException $e) {
            error_log("Printify Order Creation Error: " . $e->getMessage());
            $wcOrder->add_order_note('Failed to create Printify order: ' . $e->getMessage());
            return null;
        }
    }

    public function updateOrderStatus(string $printifyId, array $data): void
    {
        $orderId = $this->findWooCommerceOrder($printifyId);
        if (!$orderId) {
            return;
        }

        $wcOrder = wc_get_order($orderId);
        if (!$wcOrder) {
            return;
        }

        $status = $data['status'] ?? '';
        $trackingNumber = $data['tracking_number'] ?? '';
        $trackingUrl = $data['tracking_url'] ?? '';

        if ($trackingNumber && $trackingUrl) {
            update_post_meta($orderId, '_tracking_number', $trackingNumber);
            update_post_meta($orderId, '_tracking_url', $trackingUrl);
            $wcOrder->add_order_note(
                sprintf(
                    'Tracking updated - Number: %s, URL: %s',
                    $trackingNumber,
                    $trackingUrl
                )
            );
        }

        if ($status) {
            $this->updateWooCommerceStatus($wcOrder, $status);
        }
    }

    private function createOrderFromWC(\WC_Order $wcOrder): Order
    {
        return new Order([
            'id' => $wcOrder->get_id(),
            'line_items' => $wcOrder->get_items(),
            'shipping' => [
                'first_name' => $wcOrder->get_shipping_first_name(),
                'last_name' => $wcOrder->get_shipping_last_name(),
                'address_1' => $wcOrder->get_shipping_address_1(),
                'address_2' => $wcOrder->get_shipping_address_2(),
                'city' => $wcOrder->get_shipping_city(),
                'state' => $wcOrder->get_shipping_state(),
                'postcode' => $wcOrder->get_shipping_postcode(),
                'country' => $wcOrder->get_shipping_country(),
                'phone' => $wcOrder->get_billing_phone(),
                'email' => $wcOrder->get_billing_email()
            ]
        ]);
    }

    private function findWooCommerceOrder(string $printifyId): ?int
    {
        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_order_id' 
            AND meta_value = %s",
            $printifyId
        );
        return (int)$wpdb->get_var($sql) ?: null;
    }

    private function updateWooCommerceStatus(\WC_Order $order, string $printifyStatus): void
    {
        $statusMap = [
            'pending' => 'pending',
            'processing' => 'processing',
            'fulfilled' => 'completed',
            'canceled' => 'cancelled'
        ];

        $wcStatus = $statusMap[$printifyStatus] ?? null;
        if ($wcStatus) {
            $order->update_status(
                $wcStatus,
                sprintf('Printify status updated to: %s', $printifyStatus)
            );
        }
    }
}