<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class PriceCalculator
{
    private $provider_markups = [];
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->provider_markups = get_option('wpwps_provider_markups', [
            'default' => 100, // Default 100% markup
            'providers' => [
                'SPOD' => 40,     // 40% markup for SPOD
                'Monster' => 50,   // Example for other providers
            ]
        ]);
    }

    public function calculateRetailPrice($cost_price, $provider_id, $product_type = null)
    {
        $markup = $this->getProviderMarkup($provider_id, $product_type);
        return $cost_price * (1 + ($markup / 100));
    }

    private function getProviderMarkup($provider_id, $product_type = null)
    {
        $markups = $this->provider_markups['providers'];
        
        // Check for specific product type markup within provider
        if ($product_type && isset($markups[$provider_id]['types'][$product_type])) {
            return $markups[$provider_id]['types'][$product_type];
        }
        
        // Check for provider-level markup
        if (isset($markups[$provider_id])) {
            return is_array($markups[$provider_id]) ? $markups[$provider_id]['default'] : $markups[$provider_id];
        }
        
        return $this->provider_markups['default'];
    }
}
