<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class MarkupCalculator 
{
    private $logger;
    private $default_markup = 100; // Default 100% markup

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function calculateRetailPrice($cost_price, $product_id, $source = 'woocommerce')
    {
        // If price is coming from Printify order, it's already marked up
        if ($source === 'printify') {
            return $cost_price;
        }
        
        $markup_percent = $this->getMarkupForProduct($product_id);
        return $cost_price * (1 + ($markup_percent / 100));
    }

    private function getMarkupForProduct($product_id)
    {
        // Get product categories
        $categories = get_the_terms($product_id, 'product_cat');
        
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $markup = get_term_meta($category->term_id, '_wpwps_category_markup', true);
                if ($markup !== '') {
                    return floatval($markup);
                }
            }
        }

        return $this->default_markup;
    }
}
