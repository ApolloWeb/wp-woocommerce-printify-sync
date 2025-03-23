<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

class OrderRepository {
    public function findByPrintifyId(string $printify_id): ?\WC_Order {
        $query_args = [
            'limit' => 1,
            'meta_query' => [
                [
                    'key' => '_printify_order_id',
                    'value' => $printify_id
                ]
            ]
        ];

        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            return $this->findUsingHPOS($query_args);
        }

        return $this->findUsingPosts($query_args);
    }

    private function findUsingHPOS(array $query_args): ?\WC_Order {
        $data_store = \WC_Data_Store::load('order');
        if (!$data_store instanceof OrdersTableDataStore) {
            return null;
        }

        $orders = wc_get_orders($query_args);
        return !empty($orders) ? $orders[0] : null;
    }

    private function findUsingPosts(array $query_args): ?\WC_Order {
        $posts = get_posts(array_merge([
            'post_type' => 'shop_order',
            'post_status' => 'any'
        ], $query_args));

        return !empty($posts) ? wc_get_order($posts[0]) : null;
    }

    public function updatePrintifyMeta(\WC_Order $order, array $data): void {
        foreach ($data as $key => $value) {
            if (OrderUtil::custom_orders_table_usage_is_enabled()) {
                $order->update_meta_data("_printify_{$key}", $value);
            } else {
                update_post_meta($order->get_id(), "_printify_{$key}", $value);
            }
        }
        $order->save();
    }
}
