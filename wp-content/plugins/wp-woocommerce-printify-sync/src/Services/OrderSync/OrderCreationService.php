<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\OrderSync;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class OrderCreationService
{
    use TimeStampTrait;

    private PrintifyAPIClient $printifyClient;
    private LoggerInterface $logger;
    private ShippingProfileManager $shippingManager;
    
    public function __construct(
        PrintifyAPIClient $printifyClient,
        LoggerInterface $logger,
        ShippingProfileManager $shippingManager
    ) {
        $this->printifyClient = $printifyClient;
        $this->logger = $logger;
        $this->shippingManager = $shippingManager;
    }

    public function createPrintifyOrder(\WC_Order $order): bool
    {
        try {
            // Validate order items
            $items = $this->validateOrderItems($order);
            if (empty($items)) {
                throw new \Exception('No valid Printify items found in order');
            }

            // Prepare order data
            $orderData = [
                'external_id' => $order->get_id(),
                'label' => 'WC-' . $order->get_order_number(),
                'line_items' => $items,
                'shipping_method' => $this->getShippingMethod($order),
                'shipping_address' => $this->formatShippingAddress($order),
                'metadata' => [
                    'wc_order_id' => $order->get_id(),
                    'wc_order_key' => $order->get_order_key()
                ]
            ];

            // Send to Printify
            $response = $this->printifyClient->createOrder($orderData);

            if ($response && isset($response['id'])) {
                // Store Printify order ID
                $order->update_meta_data('_printify_order_id', $response['id']);
                $order->update_meta_data('_printify_order_status', 'pending');
                $order->update_meta_data('_printify_created_at', $this->getCurrentTime());
                $order->save();

                $this->logger->info('Printify order created', [
                    'wc_order_id' => $order->get_id(),
                    'printify_order_id' => $response['id']
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->logger->error('Failed to create Printify order', [
                'order_id' => $order->get_id(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function validateOrderItems(\WC_Order $order): array
    {
        $items = [];
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $printifyId = $product->get_meta('_printify_product_id');
            $printifyVariantId = $product->get_meta('_printify_variant_id');

            if ($printifyId && $printifyVariantId) {
                $items[] = [
                    'product_id' => $printifyId,
                    'variant_id' => $printifyVariantId,
                    'quantity' => $item->get_quantity()
                ];
            }
        }

        return $items;
    }

    private function formatShippingAddress(\WC_Order $order): array
    {
        return [
            'first_name' => $order->get_shipping_first_name(),
            'last_name' => $order->get_shipping_last_name(),
            'address1' => $order->get_shipping_address_1(),
            'address2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'state' => $order->get_shipping_state(),
            'country' => $order->get_shipping_country(),
            'zip' => $order->get_shipping_postcode(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone()
        ];
    }

    private function getShippingMethod(\WC_Order $order): int
    {
        $shippingMethod = $order->get_shipping_method();
        $printifyMethod = $this->shippingManager->getPrintifyMethodId($shippingMethod);
        
        return $printifyMethod ?? 1; // Default to standard shipping if not found
    }
}