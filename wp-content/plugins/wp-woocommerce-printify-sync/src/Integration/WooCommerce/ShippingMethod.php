<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Integration\WooCommerce;

class PrintifyShippingMethod extends \WC_Shipping_Method
{
    // ... existing code ...

    public function calculate_shipping($package = []): void
    {
        try {
            // Filter package items to only include Printify products
            $printifyItems = array_filter($package['contents'], function($item) {
                return get_post_meta($item['product_id'], '_printify_id', true);
            });

            if (empty($printifyItems)) {
                return;
            }

            // Get shipping rates based on retail prices
            $rates = $this->shippingService->getAvailableRates(
                $this->preparePrintifyPackage($printifyItems),
                $this->get_option('provider_profiles', [])
            );

            foreach ($rates as $rate) {
                // No markup needed as we're using retail prices
                $cost = $this->convertCurrency(
                    $rate['cost'],
                    $rate['currency'],
                    get_woocommerce_currency()
                );

                $this->add_rate([
                    'id' => $this->id . '_' . $rate['profile_id'],
                    'label' => $rate['label'],
                    'cost' => $cost,
                    'meta_data' => [
                        'printify_profile_id' => $rate['profile_id'],
                        'printify_provider_id' => $rate['provider_id'],
                        'original_currency' => $rate['currency'],
                        'original_cost' => $rate['cost']
                    ]
                ]);
            }
        } catch (\Exception $e) {
            $this->log('error', 'Failed to calculate shipping rates', [
                'error' => $e->getMessage(),
                'package' => $package
            ]);
        }
    }

    private function preparePrintifyPackage(array $items): array
    {
        return array_map(function($item) {
            $product = wc_get_product($item['variation_id'] ?? $item['product_id']);
            
            return [
                'variant_id' => get_post_meta($product->get_id(), '_printify_variant_id', true),
                'quantity' => $item['quantity'],
                'retail_price' => $product->get_price(),
                'weight_grams' => (int) ($product->get_weight() * 1000)
            ];
        }, $items);
    }
}