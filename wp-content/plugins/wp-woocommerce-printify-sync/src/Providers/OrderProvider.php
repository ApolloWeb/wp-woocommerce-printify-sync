<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderSync;

class OrderProvider implements ServiceProvider
{
    private OrderSync $orderSync;

    public function register(): void
    {
        $this->orderSync = new OrderSync();

        add_action('woocommerce_order_status_processing', [$this, 'createPrintifyOrder']);
        add_action('wpwps_order_webhook', [$this, 'handleOrderWebhook']);
        add_filter('woocommerce_order_data_store_cpt_get_orders_query', [$this, 'handleCustomOrderQuery'], 10, 2);
    }

    public function createPrintifyOrder(int $orderId): void
    {
        $wcOrder = wc_get_order($orderId);
        if (!$wcOrder) {
            return;
        }

        // Check if order contains Printify products
        $hasPrintifyProducts = false;
        foreach ($wcOrder->get_items() as $item) {
            if (get_post_meta($item->get_product_id(), '_printify_id', true)) {
                $hasPrintifyProducts = true;
                break;
            }
        }

        if ($hasPrintifyProducts) {
            $this->orderSync->createPrintifyOrder($wcOrder);
        }
    }

    public function handleOrderWebhook(array $data): void
    {
        $printifyId = $data['id'] ?? '';
        if ($printifyId) {
            $this->orderSync->updateOrderStatus($printifyId, $data);
        }
    }

    public function handleCustomOrderQuery(array $query, array $query_vars): array
    {
        if (!empty($query_vars['printify_id'])) {
            $query['meta_query'][] = [
                'key' => '_printify_order_id',
                'value' => esc_attr($query_vars['printify_id'])
            ];
        }

        return $query;
    }
}