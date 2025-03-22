<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Automattic\WooCommerce\Utilities\OrderUtil;

class IDMapper {
    const PRINTIFY_PRODUCT_META = '_printify_product_id';
    const PRINTIFY_ORDER_META = '_printify_order_id';
    const PRINTIFY_VARIANT_META = '_printify_variant_id';
    const WC_PRODUCT_META = '_wpwps_wc_product_id';
    const SYNC_STATUS_META = '_wpwps_sync_status';

    public function linkProduct($wc_product_id, $printify_product_id, $printify_variant_id = null) {
        update_post_meta($wc_product_id, self::PRINTIFY_PRODUCT_META, $printify_product_id);
        
        if ($printify_variant_id) {
            update_post_meta($wc_product_id, self::PRINTIFY_VARIANT_META, $printify_variant_id);
        }
        
        // Store reverse lookup
        update_option(
            'wpwps_product_' . $printify_product_id, 
            $wc_product_id,
            false
        );
    }

    public function linkOrder($wc_order_id, $printify_order_id) {
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            $order = wc_get_order($wc_order_id);
            $order->update_meta_data(self::PRINTIFY_ORDER_META, $printify_order_id);
            $order->save();
        } else {
            update_post_meta($wc_order_id, self::PRINTIFY_ORDER_META, $printify_order_id);
        }
        
        // Store reverse lookup
        update_option('wpwps_order_' . $printify_order_id, $wc_order_id, false);
    }

    public function getWooCommerceProductId($printify_product_id) {
        return get_option('wpwps_product_' . $printify_product_id);
    }

    public function getPrintifyProductId($wc_product_id) {
        return get_post_meta($wc_product_id, self::PRINTIFY_PRODUCT_META, true);
    }

    public function getWooCommerceOrderId($printify_order_id) {
        return get_option('wpwps_order_' . $printify_order_id);
    }

    public function getPrintifyOrderId($wc_order_id) {
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            $order = wc_get_order($wc_order_id);
            return $order ? $order->get_meta(self::PRINTIFY_ORDER_META) : false;
        }
        return get_post_meta($wc_order_id, self::PRINTIFY_ORDER_META, true);
    }

    public function isProductSynced($wc_product_id) {
        return !empty($this->getPrintifyProductId($wc_product_id));
    }

    public function isOrderSynced($wc_order_id) {
        return !empty($this->getPrintifyOrderId($wc_order_id));
    }

    public function cleanupProduct($wc_product_id) {
        $printify_id = $this->getPrintifyProductId($wc_product_id);
        if ($printify_id) {
            delete_option('wpwps_product_' . $printify_id);
        }
        delete_post_meta($wc_product_id, self::PRINTIFY_PRODUCT_META);
        delete_post_meta($wc_product_id, self::PRINTIFY_VARIANT_META);
    }

    private function isOrdersTableEnabled() {
        return class_exists(OrderUtil::class) && OrderUtil::custom_orders_table_usage_is_enabled();
    }
}
