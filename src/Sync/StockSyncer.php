<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Sync;

use ApolloWeb\WPWooCommercePrintifySync\Models\Product;
use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;

class StockSyncer {
    const HOOK = 'wpwps_sync_stock';
    const BATCH_SIZE = 50;

    public function init() {
        // Register cron schedule (4 times per day)
        add_filter('cron_schedules', [$this, 'addSixHourSchedule']);
        
        // Register cron hook
        add_action(self::HOOK, [$this, 'syncStock']);

        // Schedule if not already scheduled
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time(), 'six_hours', self::HOOK);
        }
    }

    public function addSixHourSchedule($schedules) {
        $schedules['six_hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Every 6 hours', 'wp-woocommerce-printify-sync')
        ];
        return $schedules;
    }

    public function syncStock() {
        global $wpdb;
        
        try {
            $api = new PrintifyAPI();
            $shop_id = get_option('wpwps_printify_shop_id');

            // Get all products with Printify IDs in batches
            $products = $wpdb->get_results($wpdb->prepare("
                SELECT p.ID as product_id, pm.meta_value as printify_id 
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'product'
                AND p.post_status = 'publish'
                AND pm.meta_key = '_printify_product_id'
                LIMIT %d
            ", self::BATCH_SIZE));

            foreach ($products as $product) {
                try {
                    // Get latest data from Printify
                    $data = $api->getProduct($product->printify_id, $shop_id);
                    if (empty($data)) {
                        continue;
                    }

                    // Get variation mappings
                    $variation_map = Product::getVariationIdMap($product->product_id);
                    
                    // Update each variant
                    foreach ($data['variants'] as $variant) {
                        if (!isset($variation_map[$variant['id']])) {
                            continue;
                        }

                        $wc_variation_id = $variation_map[$variant['id']];
                        $variation = wc_get_product($wc_variation_id);
                        
                        if (!$variation) {
                            continue;
                        }

                        // Update stock status
                        $is_enabled = $variant['is_enabled'] ?? true;
                        $is_available = $variant['available'] ?? true;
                        
                        if ($is_enabled && $is_available) {
                            $variation->set_stock_status('instock');
                            $variation->set_stock_quantity(999);
                        } else {
                            $variation->set_stock_status('outofstock');
                            $variation->set_stock_quantity(0);
                        }

                        $variation->save();
                    }

                    // Update last stock sync time
                    update_post_meta($product->product_id, '_printify_last_stock_sync', current_time('mysql'));

                } catch (\Exception $e) {
                    error_log(sprintf(
                        'Failed to sync stock for product %d (Printify ID: %s): %s',
                        $product->product_id,
                        $product->printify_id,
                        $e->getMessage()
                    ));
                }
            }

            // Schedule next batch if more products exist
            if (count($products) === self::BATCH_SIZE) {
                wp_schedule_single_event(time() + 300, self::HOOK);
            }

        } catch (\Exception $e) {
            error_log('Stock sync failed: ' . $e->getMessage());
        }
    }
}
