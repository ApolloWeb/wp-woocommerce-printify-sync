<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

use Automattic\WooCommerce\Utilities\OrderUtil;

class OrderSync {
    private $id_mapper;
    private $api_client;
    private $logger;

    public function syncOrder($wc_order_id) {
        try {
            // Check if already synced
            if ($this->id_mapper->isOrderSynced($wc_order_id)) {
                $printify_order_id = $this->id_mapper->getPrintifyOrderId($wc_order_id);
                return $this->updatePrintifyOrder($wc_order_id, $printify_order_id);
            }

            // Create new Printify order
            $response = $this->api_client->createOrder($this->prepareOrderData($wc_order_id));
            
            if (!empty($response['id'])) {
                $this->id_mapper->linkOrder($wc_order_id, $response['id']);
                return $response['id'];
            }

        } catch (\Exception $e) {
            $this->logger->error("Order sync failed for order #{$wc_order_id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function prepareOrderData($wc_order_id) {
        $order = wc_get_order($wc_order_id);
        $items = [];

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $printify_id = $this->id_mapper->getPrintifyProductId($product_id);
            
            if (!$printify_id) {
                throw new \Exception("Product #{$product_id} not synced with Printify");
            }

            $items[] = [
                'product_id' => $printify_id,
                'variant_id' => get_post_meta($product_id, IDMapper::PRINTIFY_VARIANT_META, true),
                'quantity' => $item->get_quantity()
            ];
        }

        return [
            'external_id' => $wc_order_id,
            'line_items' => $items
            // ...other order data...
        ];
    }

    private function updateOrderMeta($order, $key, $value) {
        if ($this->isOrdersTableEnabled()) {
            $order->update_meta_data($key, $value);
            $order->save();
        } else {
            update_post_meta($order->get_id(), $key, $value);
        }
    }

    private function getOrderMeta($order, $key) {
        if ($this->isOrdersTableEnabled()) {
            return $order->get_meta($key);
        }
        return get_post_meta($order->get_id(), $key, true);
    }

    private function isOrdersTableEnabled() {
        return class_exists(OrderUtil::class) && OrderUtil::custom_orders_table_usage_is_enabled();
    }
}
