<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Formatters;

class OrderDataFormatter {
    // ...existing code...

    private function formatLineItems(\WC_Order $order): array {
        $line_items = [];
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;
            
            $printify_id = get_post_meta($product->get_id(), '_printify_product_id', true);
            if (!$printify_id) continue;

            $line_item = [
                'product_id' => $printify_id,
                'quantity' => $item->get_quantity(),
                'print_provider_id' => get_post_meta($product->get_id(), '_printify_provider_id', true),
                'variant_id' => get_post_meta($product->get_id(), '_printify_variant_id', true),
                'metadata' => [
                    'wc_order_item_id' => $item->get_id(),
                    'sku' => $product->get_sku()
                ]
            ];

            // Add handling for personalization/customization if exists
            $personalization = $item->get_meta('_printify_personalization');
            if ($personalization) {
                $line_item['personalization'] = json_decode($personalization, true);
            }

            $line_items[] = $line_item;
        }
        
        return $line_items;
    }

    // ...existing code...
}
