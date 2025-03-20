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
            
            /** @var ProductImporterInterface $productImporter */
            $productImporter = $this->container->get('product_importer');

            $imported = [];
            $failed = [];
            
            foreach ($printifyIds as $printifyId) {
                try {
                    $productData = $printifyApi->getProduct($shopId, $printifyId);
                    $wooProductId = $productImporter->importProduct($productData);
                    $imported[] = [
                        'printify_id' => $printifyId,
                        'woo_product_id' => $wooProductId,
                        'woo_product_url' => get_permalink($wooProductId)
                    ];
                } catch (\Exception $e) {
                    $failed[] = [
                        'printify_id' => $printifyId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Update sync count
            $current_count = get_option('wpwps_products_synced', 0);
            update_option('wpwps_products_synced', $current_count + count($imported));

            wp_send_json_success([
                'message' => sprintf(
                    'Imported %d products successfully. %d products failed.',
                    count($imported),
                    count($failed)
                ),
                'imported' => $imported,
                'failed' => $failed
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Bulk import failed: ' . $e->getMessage()]);
        }
    }
}
