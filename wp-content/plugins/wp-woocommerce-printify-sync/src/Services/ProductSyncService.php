<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\DataStores\Products\ProductsTableDataStore;

class ProductSyncService
{
    private $context;
    private $logger;

    public function __construct(SyncContext $context, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->logger = $logger;
    }

    public function syncProduct(array $printifyProduct): void
    {
        global $wpdb;

        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Use HPOS product data store if available
            if (self::isHPOSEnabled()) {
                $dataStore = new ProductsTableDataStore();
                $product = new \WC_Product();
                $product->set_data_store($dataStore);
            } else {
                $product = new \WC_Product();
            }

            // Set product data
            $this->setProductData($product, $printifyProduct);

            // Save product
            $product->save();

            // Sync variants if it's a variable product
            if (!empty($printifyProduct['variants'])) {
                $this->syncVariants($product, $printifyProduct['variants']);
            }

            $wpdb->query('COMMIT');

            $this->logger->info('Product synced successfully', [
                'product_id' => $product->get_id(),
                'printify_id' => $printifyProduct['id'],
                'sync_time' => $this->context->getCurrentTime()
            ]);

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    private function syncVariants(\WC_Product $product, array $variants): void
    {
        if (self::isHPOSEnabled()) {
            $dataStore = new ProductsTableDataStore();
            foreach ($variants as $variantData) {
                $variant = new \WC_Product_Variation();
                $variant->set_data_store($dataStore);
                $this->setVariantData($variant, $variantData, $product);
                $variant->save();
            }
        } else {
            // Fall back to traditional method
            foreach ($variants as $variantData) {
                $variant = new \WC_Product_Variation();
                $this->setVariantData($variant, $variantData, $product);
                $variant->save();
            }
        }
    }

    private function setProductData(\WC_Product $product, array $data): void
    {
        // Standard product data
        $product->set_name($data['title']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_description($data['description']);
        $product->set_short_description($data['short_description'] ?? '');
        $product->set_regular_price((string)($data['price'] ?? ''));
        $product->set_sku($data['sku'] ?? '');

        // Custom meta for Printify data
        $product->update_meta_data('_printify_id', $data['id']);
        $product->update_meta_data('_printify_shop_id', $data['shop_id']);
        $product->update_meta_data('_printify_last_sync', $this->context->getCurrentTime());

        // Handle categories
        if (!empty($data['categories'])) {
            $product->set_category_ids($this->getOrCreateCategories($data['categories']));
        }

        // Handle tags
        if (!empty($data['tags'])) {
            $product->set_tag_ids($this->getOrCreateTags($data['tags']));
        }

        // Handle images
        if (!empty($data['images'])) {
            $this->handleProductImages($product, $data['images']);
        }
    }

    private function setVariantData(\WC_Product_Variation $variant, array $data, \WC_Product $parent): void
    {
        $variant->set_parent_id($parent->get_id());
        $variant->set_regular_price((string)($data['price'] ?? ''));
        $variant->set_sku($data['sku'] ?? '');
        
        // Set attributes
        if (!empty($data['attributes'])) {
            $attributes = [];
            foreach ($data['attributes'] as $name => $value) {
                $attributes['attribute_' . sanitize_title($name)] = $value;
            }
            $variant->set_attributes($attributes);
        }

        // Custom meta for Printify data
        $variant->update_meta_data('_printify_variant_id', $data['id']);
        $variant->update_meta_data('_printify_last_sync', $this->context->getCurrentTime());
    }

    public static function isHPOSEnabled(): bool
    {
        return class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class) && 
               \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }
}