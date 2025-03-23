<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class OrderCreator {
    private $api;
    private $logger;

    public function __construct(PrintifyApi $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
    }

    public function createPrintifyOrder(\WC_Order $order): array {
        try {
            $printify_data = $this->preparePrintifyOrder($order);
            $response = $this->api->createOrder($printify_data);
            
            $order->update_meta_data('_printify_order_id', $response['id']);
            $order->update_meta_data('_printify_order_status', $response['status']);
            $order->save();

            return $response;

        } catch (\Exception $e) {
            $this->logger->log("Printify order creation failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    private function preparePrintifyOrder(\WC_Order $order): array {
        $line_items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $printify_id = $product->get_meta('_printify_id');
            
            if (!$printify_id) {
                continue;
            }

            $line_items[] = [
                'product_id' => $printify_id,
                'variant_id' => $product->get_meta('_printify_variant_id'),
                'quantity' => $item->get_quantity()
            ];
        }

        if (empty($line_items)) {
            throw new \Exception('No Printify products in order');
        }

        return [
            'external_id' => $order->get_id(),
            'label' => "WC Order #{$order->get_order_number()}",
            'line_items' => $line_items,
            'shipping_address' => [
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
            ]
        ];
    }
}
