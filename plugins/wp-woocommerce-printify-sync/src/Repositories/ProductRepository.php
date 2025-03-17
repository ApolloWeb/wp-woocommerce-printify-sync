<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

use ApolloWeb\WPWooCommercePrintifySync\DataTransferObjects\PrintifyProductData;

class ProductRepository
{
    private string $currentTime;
    private string $currentUser;

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime;
        $this->currentUser = $currentUser;
    }

    public function updateProductMetadata(\WC_Product $product, PrintifyProductData $dto): void
    {
        // Basic Printify metadata
        $product->update_meta_data('_printify_id', $dto->id);
        $product->update_meta_data('_printify_blueprint_id', $dto->blueprintId);
        $product->update_meta_data('_printify_provider_id', $dto->printProviderId);
        $product->update_meta_data('_printify_shop_id', $dto->shopId);
        
        // Cost and pricing data
        $product->update_meta_data('_printify_cost_price', $dto->costPrice);
        $product->update_meta_data('_printify_retail_price', $dto->retailPrice);
        
        // Print provider data
        $product->update_meta_data('_printify_print_areas', json_encode($dto->printAreas));
        $product->update_meta_data('_printify_provider_data', json_encode($dto->printProviderData));
        
        // Shipping profiles
        $product->update_meta_data('_printify_shipping_profiles', json_encode($dto->shippingProfiles));
        
        // External reference and status
        $product->update_meta_data('_printify_external_id', $dto->externalId);
        $product->update_meta_data('_printify_is_published', $dto->isPublished);
        
        // Sync metadata
        $product->update_meta_data('_printify_last_sync', $this->currentTime);
        $product->update_meta_data('_printify_sync_user', $this->currentUser);
        $product->update_meta_data('_printify_created_at', $dto->createdAt);
        $product->update_meta_data('_printify_updated_at', $dto->updatedAt);
        
        $product->save();
    }

    public function logProductSync(int $productId, PrintifyProductData $dto, string $action = 'sync'): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_product_sync_log',
            [
                'product_id' => $productId,
                'printify_id' => $dto->id,
                'provider_id' => $dto->printProviderId,
                'action' => $action,
                'sync_data' => json_encode([
                    'cost_price' => $dto->costPrice,
                    'retail_price' => $dto->retailPrice,
                    'variants' => $dto->variants,
                    'shipping_profiles' => $dto->shippingProfiles,
                    'print_areas' => $dto->printAreas,
                    'provider_data' => $dto->printProviderData
                ]),
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ]
        );
    }
}