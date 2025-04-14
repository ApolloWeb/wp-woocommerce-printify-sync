<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Webhook;

use ApolloWeb\WPWooCommercePrintifySync\Models\Product;
use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;

class Handler {
    public function init() {
        add_action('woocommerce_api_printify_webhook', [$this, 'handleWebhook']);
    }
    
    public function handleWebhook() {
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid payload', 400);
            return;
        }
        
        // Verify webhook signature
        if (!$this->verifySignature($payload)) {
            wp_send_json_error('Invalid signature', 401);
            return;
        }
        
        switch ($data['event']) {
            case 'product.updated':
                // Find existing WC product
                $wc_product_id = Product::getWooCommerceId($data['product_id']);
                if (!$wc_product_id) {
                    error_log("No WC product found for Printify ID: {$data['product_id']}");
                    wp_send_json_error('Product not found', 404);
                    return;
                }

                try {
                    // Get latest product data
                    $api = new PrintifyAPI();
                    $product_data = $api->getProduct($data['product_id'], $data['shop_id']);
                    
                    if (!empty($product_data['variants'])) {
                        $this->updateProductPrices($wc_product_id, $product_data['variants']);
                    }
                } catch (\Exception $e) {
                    error_log("Failed to update product prices: " . $e->getMessage());
                    wp_send_json_error('Update failed', 500);
                    return;
                }
                break;
                
            case 'product.created':
                // New product, no need to check existing
                $importer = new ProductImporter();
                $importer->scheduleImport($data['product_id'], $data['shop_id']);
                break;
                
            case 'product.deleted':
                // Find and delete WC product
                $wc_product_id = Product::getWooCommerceId($data['product_id']);
                if ($wc_product_id) {
                    wp_delete_post($wc_product_id, true);
                }
                break;
        }
        
        wp_send_json_success();
    }
    
    private function verifySignature($payload) {
        $signature = $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] ?? '';
        $secret = get_option('wpwps_webhook_secret');
        
        return hash_equals(
            hash_hmac('sha256', $payload, $secret),
            $signature
        );
    }

    private function updateProductPrices($wc_product_id, $variants) {
        // Get variation ID mapping
        $variation_map = Product::getVariationIdMap($wc_product_id);
        
        foreach ($variants as $variant) {
            if (!isset($variation_map[$variant['id']])) {
                continue;
            }

            $variation_id = $variation_map[$variant['id']];
            $variation = wc_get_product($variation_id);
            
            if (!$variation) {
                continue;
            }

            // Update prices if changed
            $new_price = floatval($variant['price']);
            if ($new_price !== floatval($variation->get_regular_price())) {
                $variation->set_regular_price($new_price);
                $variation->set_price($new_price);
                $variation->save();
            }
        }

        // Update last synced time
        update_post_meta($wc_product_id, '_printify_last_synced', current_time('mysql'));
    }
}
