<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\OrderSync;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class OrderSyncService
{
    use TimeStampTrait;

    private PrintifyAPIClient $printifyClient;
    private LoggerInterface $logger;

    public function __construct(PrintifyAPIClient $printifyClient, LoggerInterface $logger)
    {
        $this->printifyClient = $printifyClient;
        $this->logger = $logger;
    }

    public function syncOrder(\WC_Order $order): void
    {
        try {
            $printifyOrderData = $this->preparePrintifyOrder($order);
            $response = $this->printifyClient->createOrder($printifyOrderData);

            if ($response['success']) {
                $order->update_meta_data('_printify_order_id', $response['order_id']);
                $order->add_order_note('Synced with Printify. Order ID: ' . $response['order_id']);
                $order->save();
            }

        } catch (\Exception $e) {
            $this->logger->error('Order sync failed', [
                'order_id' => $order->get_id(),
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }

    private function preparePrintifyOrder(\WC_Order $order): array
    {
        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $printifyId = $product->get_meta('_printify_product_id');
            
            if ($printifyId) {
                $items[] = [
                    'product_id' => $printifyId,
                    'variant_id' => $product->get_meta('_printify_variant_id'),
                    'quantity' => $item->get_quantity()
                ];
            }
        }

        return [
            'external_id' => $order->get_id(),
            'shipping_method' => 1, // Standard shipping
            'send_shipping_notification' => true,
            'address' => $this->getShippingAddress($order),
            'line_items' => $items
        ];
    }

    private function getShippingAddress(\WC_Order $order): array
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
}