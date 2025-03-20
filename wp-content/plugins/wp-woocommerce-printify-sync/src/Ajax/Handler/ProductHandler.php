<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler;

use ApolloWeb\WPWooCommercePrintifySync\Core\Cache;
use ApolloWeb\WPWooCommercePrintifySync\API\Interfaces\PrintifyAPIInterface;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\ProductImporterInterface;

class ProductHandler extends AbstractAjaxHandler
{
    /**
     * Handle product fetch request
     */
    public function handle(): void
    {
        try {
            // Accept values from both GET and POST
            $page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
            $perPage = isset($_REQUEST['per_page']) ? (int)$_REQUEST['per_page'] : 50; // Default 50
            $refreshCache = isset($_REQUEST['refresh_cache']) && $_REQUEST['refresh_cache'] === 'true';

            $shopId = $this->getShopId();

            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            // Clear cache if requested
            if ($refreshCache) {
                Cache::deleteProducts($shopId);
            }

            $result = $printifyApi->getProducts($shopId, $page, $perPage);
            
            if (!isset($result['data'])) {
                throw new \Exception('Invalid API response format - missing data array');
            }
            
            // Process the products
            $processed = $this->processProducts($result['data']);

            // Log the pagination info for debugging
            error_log("API Response - Current page: {$result['current_page']}, Last page: {$result['last_page']}, Total: {$result['total']}");

            wp_send_json_success([
                'products' => $processed,
                'total' => $result['total'] ?? 0,
                'current_page' => $result['current_page'] ?? $page,
                'last_page' => $result['last_page'] ?? ceil(($result['total'] ?? count($processed)) / $perPage),
                'per_page' => $perPage
            ]);

        } catch (\Exception $e) {
            error_log('Error fetching products: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'Error fetching products: ' . $e->getMessage(),
                'error_type' => 'api'
            ]);
        }
    }
    
    /**
     * Process products data
     * 
     * @param array $products Raw products data
     * @return array Processed products
     */
    protected function processProducts(array $products): array
    {
        $processed = [];
        /** @var ProductImporterInterface $productImporter */
        $productImporter = $this->container->get('product_importer');
        
        foreach ($products as $product) {
            if (!isset($product['id'])) {
                continue;
            }
            
            $printifyId = $product['id'];
            $wooProductId = $productImporter->getWooProductIdByPrintifyId($printifyId);
            
            $processed[] = [
                'printify_id' => $printifyId,
                'title' => $product['title'] ?? 'Untitled Product',
                'thumbnail' => isset($product['images'][0]['src']) ? $product['images'][0]['src'] : '',
                'woo_product_id' => $wooProductId,
                'status' => !empty($product['visible']) ? 'active' : 'draft',
                'last_updated' => date('Y-m-d H:i:s', strtotime($product['updated_at'] ?? 'now')),
                'is_imported' => !empty($wooProductId)
            ];
        }
        
        return $processed;
    }
    
    /**
     * Import a product to WooCommerce
     */
    public function importProduct(): void
    {
        try {
            $printifyId = sanitize_text_field($_POST['printify_id'] ?? '');
            
            if (empty($printifyId)) {
                wp_send_json_error(['message' => 'Printify product ID is required']);
                return;
            }
            
            $shopId = $this->getShopId();
            
            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            // Get product from API
            $productData = $printifyApi->getProduct($shopId, $printifyId);
            
            /** @var ProductImporterInterface $productImporter */
            $productImporter = $this->container->get('product_importer');
            
            // Import product
            $wooProductId = $productImporter->importProduct($productData);
            
            wp_send_json_success([
                'message' => 'Product imported successfully',
                'woo_product_id' => $wooProductId,
                'woo_product_url' => get_permalink($wooProductId),
                'products_synced' => get_option('wpwps_products_synced', 0)
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Failed to import product: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Import multiple products in bulk
     */
    public function bulkImportProducts(): void
    {
        try {
            $printifyIds = isset($_POST['printify_ids']) ? (array)$_POST['printify_ids'] : [];
            
            if (empty($printifyIds)) {
                wp_send_json_error(['message' => 'No products selected for import']);
                return;
            }

            $shopId = $this->getShopId();
            
            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            $products = [];
            
            // Fetch each product from the API and collect them
            foreach ($printifyIds as $printifyId) {
                try {
                    $productData = $printifyApi->getProduct($shopId, $printifyId);
                    $products[] = $productData;
                } catch (\Exception $e) {
                    error_log('Error fetching product for batch import: ' . $e->getMessage());
                    // Continue with other products
                }
            }
            
            if (empty($products)) {
                wp_send_json_error(['message' => 'Failed to fetch any products for import']);
                return;
            }
            
            // Schedule batch import using Action Scheduler
            do_action('wpwps_start_product_import', $products, $shopId);
            
            wp_send_json_success([
                'message' => sprintf(
                    'Scheduled import of %d products. The import will continue in the background.',
                    count($products)
                ),
                'status' => 'scheduled'
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Failed to schedule product import: ' . $e->getMessage()]);
        }
    }

    /**
     * Import all products from Printify
     */
    public function importAllProducts(): void
    {
        try {
            $shopId = $this->getShopId();
            
            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            // Check if the shop is valid
            try {
                $printifyApi->getShop($shopId);
            } catch (\Exception $e) {
                wp_send_json_error(['message' => 'Invalid Printify shop: ' . $e->getMessage()]);
                return;
            }
            
            // Schedule the import of all products
            do_action('wpwps_start_all_products_import', $shopId);
            
            wp_send_json_success([
                'message' => 'Started importing all products from Printify. This will run in the background.',
                'status' => 'scheduled'
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Failed to start importing all products: ' . $e->getMessage()]);
        }
    }
}
