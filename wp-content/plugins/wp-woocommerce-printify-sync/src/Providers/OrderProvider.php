<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Core\PrintifyClient;
use GuzzleHttp\Exception\GuzzleException;

class OrderProvider extends ServiceProvider
{
    private const OPTION_PREFIX = 'wpwps_';
    private PrintifyClient $client;
    private array $customStatuses = [
        'pre_production' => [
            'on-hold', 'awaiting-customer-evidence', 'submit-order', 'action-required'
        ],
        'production' => [
            'in-production', 'has-issues', 'canceled-provider', 'canceled'
        ],
        'shipping' => [
            'ready-to-ship', 'shipped', 'out-for-delivery', 'delivered', 'shipping-issue'
        ],
        'refund_reprint' => [
            'refund-requested', 'refund-approved', 'refund-declined',
            'reprint-requested', 'reprint-approved', 'reprint-declined',
            'awaiting-customer-evidence'
        ]
    ];

    public function boot(): void
    {
        $this->client = new PrintifyClient($this->getApiKey());

        $this->registerAdminMenu(
            'WC Printify Orders',
            'Orders',
            'manage_woocommerce',
            'wpwps-orders',
            [$this, 'renderOrdersPage']
        );

        $this->registerCustomOrderStatuses();
        $this->registerAjaxEndpoint('wpwps_sync_orders', [$this, 'syncOrders']);
        $this->registerAjaxEndpoint('wpwps_update_order_status', [$this, 'updateOrderStatus']);

        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChange'], 10, 4);
        add_action('woocommerce_thankyou', [$this, 'handleNewOrder']);
    }

    public function renderOrdersPage(): void
    {
        $data = [
            'orders' => $this->getOrders(),
            'statuses' => $this->getOrderStatuses(),
            'sync_stats' => [
                'total' => $this->getTotalOrders(),
                'synced' => $this->getSyncedOrdersCount(),
                'pending' => $this->getPendingOrdersCount(),
                'failed' => $this->getFailedOrdersCount()
            ]
        ];

        echo $this->view->render('wpwps-orders', $data);
    }

    private function registerCustomOrderStatuses(): void
    {
        foreach ($this->customStatuses as $group => $statuses) {
            foreach ($statuses as $status) {
                $statusId = 'wc-' . $status;
                register_post_status($statusId, [
                    'label' => ucwords(str_replace('-', ' ', $status)),
                    'public' => true,
                    'exclude_from_search' => false,
                    'show_in_admin_all_list' => true,
                    'show_in_admin_status_list' => true,
                    'label_count' => _n_noop(
                        ucwords(str_replace('-', ' ', $status)) . ' <span class="count">(%s)</span>',
                        ucwords(str_replace('-', ' ', $status)) . ' <span class="count">(%s)</span>'
                    )
                ]);

                add_filter('wc_order_statuses', function($orderStatuses) use ($status, $statusId) {
                    $orderStatuses[$statusId] = ucwords(str_replace('-', ' ', $status));
                    return $orderStatuses;
                });
            }
        }
    }

    public function syncOrders(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        try {
            $shopId = get_option(self::OPTION_PREFIX . 'shop_id');
            $orders = $this->client->get("shops/{$shopId}/orders.json");

            foreach ($orders as $order) {
                $this->updateOrderStatus(
                    $order['external_id'],
                    $order['status']
                );
            }

            wp_send_json_success(['message' => 'Orders synchronized successfully']);
        } catch (GuzzleException $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handleNewOrder(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (!$order) return;

        try {
            $shopId = get_option(self::OPTION_PREFIX . 'shop_id');
            $orderData = $this->prepareOrderData($order);
            
            $printifyOrder = $this->client->post("shops/{$shopId}/orders.json", $orderData);
            $this->attachPrintifyOrderMeta($orderId, $printifyOrder);
        } catch (GuzzleException $e) {
            error_log('WPWPS Order Creation Error: ' . $e->getMessage());
            update_post_meta($orderId, '_printify_sync_failed', '1');
        }
    }

    private function prepareOrderData($order): array
    {
        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $printifyProductId = get_post_meta($product->get_id(), '_printify_product_id', true);
            if (!$printifyProductId) continue;

            $items[] = [
                'product_id' => $printifyProductId,
                'variant_id' => get_post_meta($item->get_variation_id(), '_printify_variant_id', true),
                'quantity' => $item->get_quantity()
            ];
        }

        return [
            'external_id' => $order->get_id(),
            'line_items' => $items,
            'shipping_method' => $order->get_shipping_method(),
            'shipping_address' => [
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'address1' => $order->get_shipping_address_1(),
                'address2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'country' => $order->get_shipping_country(),
                'zip' => $order->get_shipping_postcode(),
                'phone' => $order->get_billing_phone(),
                'email' => $order->get_billing_email()
            ]
        ];
    }

    private function attachPrintifyOrderMeta(int $orderId, array $printifyOrder): void
    {
        update_post_meta($orderId, '_printify_order_id', $printifyOrder['id']);
        update_post_meta($orderId, '_printify_order_status', $printifyOrder['status']);
        update_post_meta($orderId, '_printify_shipping_cost_usd', $printifyOrder['shipping_cost']);
        update_post_meta($orderId, '_printify_provider_ids', json_encode($printifyOrder['provider_ids']));
    }

    public function handleOrderStatusChange(int $orderId, string $oldStatus, string $newStatus, $order): void
    {
        if (!$order) return;

        $printifyOrderId = get_post_meta($orderId, '_printify_order_id', true);
        if (!$printifyOrderId) return;

        try {
            $shopId = get_option(self::OPTION_PREFIX . 'shop_id');
            $this->client->post("shops/{$shopId}/orders/{$printifyOrderId}/status.json", [
                'json' => ['status' => $this->mapWooStatusToPrintify($newStatus)]
            ]);
        } catch (GuzzleException $e) {
            error_log('WPWPS Order Status Update Error: ' . $e->getMessage());
        }
    }

    private function mapWooStatusToPrintify(string $wooStatus): string
    {
        $statusMap = [
            'pending' => 'pending',
            'processing' => 'in_production',
            'on-hold' => 'on_hold',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded'
        ];

        return $statusMap[$wooStatus] ?? 'pending';
    }

    private function getOrders(): array
    {
        global $wpdb;
        $orders = wc_get_orders([
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => '_printify_order_id',
            'meta_compare' => 'EXISTS'
        ]);

        return array_map(function($order) {
            return [
                'id' => $order->get_id(),
                'printify_id' => get_post_meta($order->get_id(), '_printify_order_id', true),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name()
            ];
        }, $orders);
    }

    private function getOrderStatuses(): array
    {
        $statuses = [];
        foreach ($this->customStatuses as $group => $groupStatuses) {
            $statuses[$group] = array_map(function($status) {
                return [
                    'id' => 'wc-' . $status,
                    'label' => ucwords(str_replace('-', ' ', $status))
                ];
            }, $groupStatuses);
        }
        return $statuses;
    }

    private function getTotalOrders(): int
    {
        return wc_get_orders(['return' => 'ids', 'limit' => -1]);
    }

    private function getSyncedOrdersCount(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_order_id'"
        );
    }

    private function getPendingOrdersCount(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND pm.meta_key = '_printify_order_id' IS NULL"
        );
    }

    private function getFailedOrdersCount(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND pm.meta_key = '_printify_sync_failed'
            AND pm.meta_value = '1'"
        );
    }

    private function getApiKey(): string
    {
        return get_option(self::OPTION_PREFIX . 'api_key', '');
    }
}