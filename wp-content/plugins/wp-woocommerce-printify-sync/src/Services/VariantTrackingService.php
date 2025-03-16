<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Context\SyncContext;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\DataTransferObjects\PrintifyProductData;

class VariantTrackingService
{
    private LoggerInterface $logger;
    private SyncContext $context;

    public function __construct(LoggerInterface $logger, SyncContext $context)
    {
        $this->logger = $logger;
        $this->context = $context;
    }

    public function syncVariants(\WC_Product_Variable $product, PrintifyProductData $dto): void
    {
        // Delete existing variations
        $this->deleteExistingVariations($product);

        foreach ($dto->variants as $variantData) {
            try {
                $variation = $this->createVariation($product, $variantData);
                $this->trackVariant($product->get_id(), $variation->get_id(), $variantData, $dto);
            } catch (\Exception $e) {
                $this->logger->error('Failed to sync variant', [
                    'product_id' => $product->get_id(),
                    'variant_id' => $variantData['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function createVariation(\WC_Product_Variable $product, array $variantData): \WC_Product_Variation
    {
        $variation = new \WC_Product_Variation();
        $variation->set_parent_id($product->get_id());
        
        // Set basic data
        $variation->set_regular_price((string)$variantData['retail_price']);
        $variation->set_sku($variantData['sku']);
        
        // Set attributes
        $attributes = [];
        foreach ($variantData['options'] as $name => $value) {
            $attributes['attribute_' . sanitize_title($name)] = sanitize_title($value);
        }
        $variation->set_attributes($attributes);
        
        // Set Printify metadata
        $variation->update_meta_data('_printify_variant_id', $variantData['id']);
        $variation->update_meta_data('_printify_cost_price', $variantData['cost_price']);
        $variation->update_meta_data('_printify_retail_price', $variantData['retail_price']);
        $variation->update_meta_data('_printify_provider_data', json_encode($variantData['print_provider_data'] ?? []));
        
        $variation->save();
        
        return $variation;
    }

    private function trackVariant(int $productId, int $variationId, array $variantData, PrintifyProductData $dto): void
    {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wpwps_variant_tracking',
            [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'printify_variant_id' => $variantData['id'],
                'printify_sku' => $variantData['sku'],
                'cost_price' => $variantData['cost_price'],
                'retail_price' => $variantData['retail_price'],
                'attributes' => json_encode($variantData['options']),
                'print_provider_data' => json_encode($variantData['print_provider_data'] ?? []),
                'shipping_data' => json_encode($variantData['shipping'] ?? []),
                'sync_id' => $dto->syncId,
                'created_at' => $this->context->getCurrentTime(),
                'updated_at' => $this->context->getCurrentTime(),
                'created_by' => $this->context->getCurrentUser(),
                'updated_by' => $this->context->getCurrentUser()
            ]
        );
    }

    private function deleteExistingVariations(\WC_Product_Variable $product): void
    {
        $variations = $product->get_children();
        foreach ($variations as $variationId) {
            wp_delete_post($variationId, true);
        }
    }
}