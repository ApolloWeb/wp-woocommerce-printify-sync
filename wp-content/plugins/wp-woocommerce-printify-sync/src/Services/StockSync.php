<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class StockSync {
    private $api_service;
    private $rate_limiter;
    private $logger;

    public function __construct(ApiService $api_service, RateLimiter $rate_limiter, LoggerService $logger) {
        $this->api_service = $api_service;
        $this->rate_limiter = $rate_limiter;
        $this->logger = $logger;

        // Register cron hook
        add_action('wpwps_stock_sync', [$this, 'syncStockLevels']);
    }

    public function scheduleCron(): void {
        if (!wp_next_scheduled('wpwps_stock_sync')) {
            wp_schedule_event(time(), 'twicedaily', 'wpwps_stock_sync');
        }
    }

    public function syncStockLevels(): void {
        global $wpdb;

        // Get products with Printify IDs
        $products = $wpdb->get_results(
            "SELECT post_id, meta_value as printify_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id'"
        );

        foreach ($products as $product) {
            if (!$this->rate_limiter->checkLimit('stock_sync')) {
                $this->logger->warning('Rate limit reached during stock sync');
                return;
            }

            try {
                $this->syncProductStock($product->post_id, $product->printify_id);
            } catch (\Exception $e) {
                $this->logger->error('Stock sync failed', [
                    'product_id' => $product->post_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function syncProductStock(int $product_id, string $printify_id): void {
        $response = $this->api_service->get("products/{$printify_id}/variants");
        
        if (!$response || !isset($response['variants'])) {
            throw new \Exception('Invalid API response');
        }

        $product = wc_get_product($product_id);
        if (!$product) return;

        if ($product->is_type('variable')) {
            foreach ($response['variants'] as $variant) {
                $variation_id = get_post_meta($product_id, '_printify_variant_' . $variant['id'], true);
                if ($variation = wc_get_product($variation_id)) {
                    $variation->set_stock_quantity($variant['quantity']);
                    $variation->save();
                }
            }
        } else {
            $product->set_stock_quantity($response['variants'][0]['quantity'] ?? 0);
            $product->save();
        }
    }
}
