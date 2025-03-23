<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ProductCreator {
    private $api;
    private $logger;

    public function __construct(PrintifyApi $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
    }

    public function createFromPrintify(array $printify_data, bool $publish = false): int {
        try {
            // Create base product
            $product = new \WC_Product_Variable();
            $product->set_name($printify_data['title']);
            $product->set_description($printify_data['description']);
            $product->set_status($publish ? 'publish' : 'draft');
            
            // Set metadata
            $product->update_meta_data('_printify_id', $printify_data['id']);
            $product->update_meta_data('_printify_shop_id', $printify_data['shop_id']);
            $product->update_meta_data('_printify_blueprint_id', $printify_data['blueprint_id']);
            
            $product_id = $product->save();

            // Create variations
            if (!empty($printify_data['variants'])) {
                $this->createVariations($product_id, $printify_data['variants']);
            }

            // Set images
            if (!empty($printify_data['images'])) {
                $this->setImages($product_id, $printify_data['images']);
            }

            return $product_id;

        } catch (\Exception $e) {
            $this->logger->log("Product creation failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    private function createVariations(int $product_id, array $variants): void {
        foreach ($variants as $variant_data) {
            $variation = new \WC_Product_Variation();
            $variation->set_parent_id($product_id);
            $variation->set_attributes($this->mapVariantAttributes($variant_data));
            $variation->set_regular_price($variant_data['price']);
            $variation->set_sku($variant_data['sku']);
            $variation->update_meta_data('_printify_variant_id', $variant_data['id']);
            $variation->save();
        }
    }

    private function mapVariantAttributes(array $variant_data): array {
        $attributes = [];
        foreach ($variant_data['options'] as $option) {
            $attributes[$option['name']] = $option['value'];
        }
        return $attributes;
    }
}
