<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Cart;

class MultiProviderNotice
{
    public function addCartNotice(): void
    {
        $providers = $this->getCartProviders();
        
        if (count($providers) > 1) {
            wc_add_notice(
                __(
                    'Your cart contains items from multiple print providers. 
                    You will need to select shipping methods for each provider.',
                    'wp-woocommerce-printify-sync'
                ),
                'notice'
            );
        }
    }

    private function getCartProviders(): array
    {
        $providers = [];
        
        foreach (WC()->cart->get_cart() as $cartItem) {
            $providerId = get_post_meta(
                $cartItem['product_id'],
                '_printify_provider_id',
                true
            );
            
            if ($providerId) {
                $providers[$providerId] = get_option(
                    "printify_provider_{$providerId}"
                )['name'] ?? $providerId;
            }
        }
        
        return $providers;
    }
}