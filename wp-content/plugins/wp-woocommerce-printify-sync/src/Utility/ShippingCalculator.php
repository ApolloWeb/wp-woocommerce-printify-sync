<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Utility;

/**
 * Utility class for calculating shipping costs with tiered rates
 */
class ShippingCalculator
{
    /**
     * Calculate shipping cost for an order with tiered shipping rates
     * 
     * @param array $lineItems Array of line items with shipping costs
     * @param float $firstItemRate Rate for first item (null to use the first item's shipping cost)
     * @param float $additionalItemRate Rate for additional items (null to calculate automatically)
     * @return array Shipping cost breakdown [first_item, additional_items, total]
     */
    public static function calculateTieredShipping(array $lineItems, ?float $firstItemRate = null, ?float $additionalItemRate = null): array
    {
        if (empty($lineItems)) {
            return [
                'first_item' => 0,
                'additional_items' => 0,
                'total' => 0
            ];
        }
        
        // Sort line items by shipping cost (highest first) to ensure we use the highest shipping cost for the first item
        usort($lineItems, function($a, $b) {
            $aShipping = (float)($a['shipping_cost'] ?? 0);
            $bShipping = (float)($b['shipping_cost'] ?? 0);
            return $bShipping <=> $aShipping;
        });
        
        // Get total items count (accounting for quantities)
        $totalItems = 0;
        foreach ($lineItems as $item) {
            $totalItems += (int)($item['quantity'] ?? 1);
        }
        
        // If we only have one item, return its shipping cost
        if (count($lineItems) === 1 && ($lineItems[0]['quantity'] ?? 1) === 1) {
            $singleItemShipping = (float)($lineItems[0]['shipping_cost'] ?? 0) / 100;
            return [
                'first_item' => $singleItemShipping,
                'additional_items' => 0,
                'total' => $singleItemShipping
            ];
        }
        
        // Calculate first item shipping rate
        $firstItemShipping = $firstItemRate !== null 
            ? $firstItemRate 
            : (float)($lineItems[0]['shipping_cost'] ?? 0) / 100;
        
        // Calculate additional items rate if not provided
        if ($additionalItemRate === null) {
            // Default to 50% of first item rate if we don't have specific information
            $additionalItemRate = $firstItemShipping * 0.5;
            
            // If we have more line items with shipping costs, use the average of those
            if (count($lineItems) > 1) {
                $additionalShippingCosts = 0;
                $additionalItemsCount = 0;
                
                // Start from index 1 (skip the first item)
                for ($i = 1; $i < count($lineItems); $i++) {
                    $additionalShippingCosts += (float)($lineItems[$i]['shipping_cost'] ?? 0) / 100;
                    $additionalItemsCount++;
                }
                
                if ($additionalItemsCount > 0) {
                    $additionalItemRate = $additionalShippingCosts / $additionalItemsCount;
                }
            }
        }
        
        // Calculate additional items shipping
        $additionalItemsShipping = ($totalItems - 1) * $additionalItemRate;
        
        // Calculate total shipping
        $totalShipping = $firstItemShipping + $additionalItemsShipping;
        
        return [
            'first_item' => $firstItemShipping,
            'additional_items' => $additionalItemsShipping,
            'total' => $totalShipping
        ];
    }
}
