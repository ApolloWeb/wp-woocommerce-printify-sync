<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax\Handlers;

use ApolloWeb\WPWooCommercePrintifySync\API\Interfaces\PrintifyAPIInterface;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\ProductImporterInterface;
use ApolloWeb\WPWooCommercePrintifySync\Core\Cache;

class ProductHandler extends BaseHandler
{
    public function fetchProducts()
    {
        try {
            $this->verifyGetRequest();
            $shopId = $this->verifyShopId();
            $pagination = $this->getPaginationParams();
            
            /** @var PrintifyAPIInterface $printifyApi */
            $printifyApi = $this->container->get('printify_api');
            
            $refreshCache = isset($_GET['refresh_cache']) && $_GET['refresh_cache'] === 'true';
            if ($refreshCache) {
                Cache::deleteProducts($shopId);
            }

            $result = $printifyApi->getProducts($shopId, $pagination['page'], $pagination['per_page']);
            
            if (!isset($result['data'])) {
                throw new \Exception('Invalid API response format - missing data array');
            }

            wp_send_json_success([
                'products' => $this->processProducts($result['data']),
                'total' => $result['total'] ?? 0,
                'current_page' => $result['current_page'] ?? $pagination['page'],
                'last_page' => $result['last_page'] ?? 1,
                'per_page' => $result['per_page'] ?? $pagination['per_page']
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => 'Error fetching products: ' . $e->getMessage(),
                'error_type' => 'api'
            ]);
        }
    }

    private function processProducts(array $products): array
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
                'thumbnail' => $product['images'][0]['src'] ?? '',
                'woo_product_id' => $wooProductId,
                'status' => !empty($product['visible']) ? 'active' : 'draft',
                'last_updated' => date('Y-m-d H:i:s', strtotime($product['updated_at'] ?? 'now')),
                'is_imported' => !empty($wooProductId)
            ];
        }

        return $processed;
    }
}
