<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class OrderSyncManager extends AbstractService
{
    private PrintifyAPI $api;
    private array $statusMap = [
        'pending' => 'draft',
        'processing' => 'pending',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
        'refunded' => 'cancelled',
        'failed' => 'cancelled',
        'on-hold' => 'pending'
    ];

    public function createPrintifyOrder(\WC_Order $order): ?string
    {
        try {
            // Prepare order data
            $orderData = $this->preparePrintifyOrderData($order);

            // Create order in Printify
            $response = $this->api->createOrder($orderData);

            // Store Printify order ID
            $order->update_meta_data('_printify_order_id', $response['id']);
            $order->save();

            $this->logOperation('createPrintifyOrder', [
                'wc_order_id' => $order->get_id(),
                'printify_order_id' => $response['id']
            ]);

            return $response['id'];

        } catch (\Exception $e) {
            $this->logError('createPrintifyOrder', $e, [
                'wc_order_id' => $order->get_id()
            ]);
            return null;
        }
    }

    private function preparePrintifyOrderData(\WC_Order $order): array
    {
        return [
            'external_id' => $order->get_id(),
            'shipping_method' => 1, // Standard shipping
            'shipping_address' => [
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'address1' => $order->get_shipping_address_1(),
                'address2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'zip' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone()
            ],
            'line_items' => $this->prepareLineItems($order)
        ];
    }

    private function prepareLineItems(\WC_Order $order): array
    {
        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $printifyId = get_post_meta($product->get_id(), '_printify_id', true);
            if (!$printifyId) continue;

            $items[] = [
                'product_id' => $printifyId,
                'variant_id' => $this->getPrintifyVariantId($product, $item),
                'quantity' => $item->get_quantity()
            ];
        }
        return $items;
    }
}