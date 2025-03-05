            'address1' => $order->get_shipping_address_1(),
            'address2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'state' => $order->get_shipping_state(),
            'zip' => $order->get_shipping_postcode(),
            'country' => $order->get_shipping_country(),
            'phone' => $order->get_billing_phone(),
            'email' => $order->get_billing_email()
        );
        
        // Prepare order data
        $order_data = array(
            'line_items' => $items,
            'shipping_address' => $shipping_address,
            'send_shipping_notification' => true,
            'external_id' => $order->get_id(),
            'metadata' => array(
                'source' => 'woocommerce',
                'woocommerce_order_id' => $order->get_id(),
                'woocommerce_order_number' => $order->get_order_number(),
                'version' => WPWPRINTIFYSYNC_VERSION,
                'timestamp' => $this->timestamp
            )
        );
        
        return $order_data;
    }
    
    /**
     * Map Printify status to WooCommerce status
     *
     * @param string $printify_status Printify order status
     * @return string WooCommerce order status
     */
    private function map_printify_status_to_wc($printify_status) {
        $status_map = array(
            'pending' => 'processing',
            'on-hold' => 'on-hold',
            'fulfilled' => 'completed',
            'canceled' => 'cancelled'
        );
        
        return isset($status_map[$printify_status]) ? $status_map[$printify_status] : 'processing';
    }
}