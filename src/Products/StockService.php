<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Products;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiInterface;
use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsServiceInterface;

/**
 * Service for managing product stock levels
 */
class StockService {
    /**
     * @var PrintifyApiInterface
     */
    private $api;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var SettingsServiceInterface
     */
    private $settings;
    
    /**
     * @var string
     */
    private $shop_id;
    
    /**
     * Constructor
     *
     * @param PrintifyApiInterface $api
     * @param LoggerInterface $logger
     * @param SettingsServiceInterface $settings
     */
    public function __construct(
        PrintifyApiInterface $api,
        LoggerInterface $logger,
        SettingsServiceInterface $settings
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->settings = $settings;
        
        $printify_settings = $this->settings->getPrintifySettings();
        $this->shop_id = $printify_settings['shop_id'];
    }
    
    /**
     * Synchronize stock levels for all products and variants
     * 
     * @return array Statistics about the sync process
     */
    public function synchronize_stock_levels() {
        $stats = [
            'products_updated' => 0,
            'variants_processed' => 0,
            'variants_out_of_stock' => 0,
            'variants_in_stock' => 0,
            'errors' => 0
        ];
        
        try {
            $printify_products = $this->get_all_mappings();
            
            foreach ($printify_products as $mapping) {
                $printify_id = $mapping->printify_product_id;
                $wc_product_id = $mapping->wc_product_id;
                
                try {
                    // Get stock data from Printify API
                    $printify_data = $this->api->get_product($this->shop_id, $printify_id);
                    
                    if (is_wp_error($printify_data)) {
                        $this->logger->log_error(
                            'stock_sync',
                            sprintf('Error fetching product data for %s: %s', $printify_id, $printify_data->get_error_message())
                        );
                        $stats['errors']++;
                        continue;
                    }
                    
                    // Process variant stock levels
                    if (!empty($printify_data['variants'])) {
                        $updated = $this->update_product_stock($wc_product_id, $printify_data['variants']);
                        
                        if ($updated) {
                            $stats['products_updated']++;
                            $stats['variants_processed'] += count($printify_data['variants']);
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->log_error(
                        'stock_sync',
                        sprintf('Error processing stock for product %s: %s', $printify_id, $e->getMessage())
                    );
                    $stats['errors']++;
                }
            }
        } catch (\Exception $e) {
            $this->logger->log_error(
                'stock_sync',
                sprintf('Error in stock synchronization: %s', $e->getMessage())
            );
            $stats['errors']++;
        }
        
        return $stats;
    }
    
    /**
     * Get all Printify to WooCommerce product mappings
     * 
     * @return array Product mappings
     */
    private function get_all_mappings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwps_printify_product_map';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return [];
        }
        
        return $wpdb->get_results("SELECT * FROM $table_name WHERE sync_status = 'synced'");
    }
    
    /**
     * Update stock levels for a product and its variants
     * 
     * @param int $product_id WooCommerce product ID
     * @param array $variants Printify product variants
     * @return bool Whether any stock was updated
     */
    private function update_product_stock($product_id, $variants) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            $this->logger->log_warning('stock_sync', sprintf('Product %d not found in WooCommerce', $product_id));
            return false;
        }
        
        $updated = false;
        
        // Simple product
        if ($product->is_type('simple')) {
            // For simple products, use the first variant's stock
            if (!empty($variants[0])) {
                $this->update_stock_for_variant($product, $variants[0]);
                $updated = true;
            }
        } 
        // Variable product
        elseif ($product->is_type('variable')) {
            $variations = $product->get_children();
            
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                if (!$variation) continue;
                
                // Get the corresponding Printify variant ID
                $printify_variant_id = $variation->get_meta('_printify_variant_id', true);
                
                if (!$printify_variant_id) continue;
                
                // Find matching variant from Printify data
                foreach ($variants as $variant) {
                    if ($variant['id'] === $printify_variant_id || $variant['id'] == $printify_variant_id) {
                        $this->update_stock_for_variant($variation, $variant);
                        $updated = true;
                        break;
                    }
                }
            }
        }
        
        return $updated;
    }
    
    /**
     * Update stock level for a specific product/variation
     * 
     * @param \WC_Product $product WooCommerce product or variation
     * @param array $variant Printify variant data
     * @return bool Whether stock was updated
     */
    private function update_stock_for_variant($product, $variant) {
        // Check if we have stock quantity information
        if (!isset($variant['quantity'])) {
            return false;
        }
        
        $printify_stock = (int)$variant['quantity'];
        $current_stock = $product->get_stock_quantity();
        
        // Only update if stock has changed
        if ($current_stock !== $printify_stock) {
            // Save original Printify stock data for reference
            $product->update_meta_data('_printify_last_stock_update', current_time('mysql'));
            $product->update_meta_data('_printify_stock_quantity', $printify_stock);
            
            // Update WooCommerce stock
            $product->set_stock_quantity($printify_stock);
            
            // Update stock status based on quantity
            if ($printify_stock <= 0) {
                $product->set_stock_status('outofstock');
            } else {
                $product->set_stock_status('instock');
            }
            
            $product->save();
            
            $this->logger->log_info(
                'stock_sync',
                sprintf(
                    'Updated stock for product %d from %s to %s',
                    $product->get_id(),
                    ($current_stock !== null ? $current_stock : 'null'),
                    $printify_stock
                )
            );
            
            return true;
        }
        
        return false;
    }
}
