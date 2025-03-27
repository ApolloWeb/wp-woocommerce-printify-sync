<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\BaseServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Core\HPOSCompatibility;
use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyClient;

class WooCommerceServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     * 
     * @return void
     */
    public function register(): void
    {
        // Register WooCommerce hooks and actions
        add_action('woocommerce_checkout_order_processed', [$this, 'handleOrderCreation'], 10, 3);
        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChanged'], 10, 4);
        add_action('woocommerce_admin_order_data_after_order_details', [$this, 'addPrintifyMetaboxToOrder']);
        add_action('woocommerce_product_options_general_product_data', [$this, 'addPrintifyProductFields']);
        add_action('woocommerce_process_product_meta', [$this, 'savePrintifyProductFields']);
        
        // Add column to orders list
        add_filter('manage_edit-shop_order_columns', [$this, 'addPrintifyOrderColumn']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'populatePrintifyOrderColumn'], 10, 2);
        
        // Add compatibility with custom order tables (HPOS)
        if (class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')) {
            add_action('woocommerce_shop_order_list_table_custom_column', [$this, 'populateHPOSPrintifyOrderColumn'], 10, 2);
        }
    }

    /**
     * Add Printify column to orders list.
     * 
     * @param array $columns
     * @return array
     */
    public function addPrintifyOrderColumn(array $columns): array
    {
        $new_columns = [];
        
        foreach ($columns as $column_name => $column_info) {
            $new_columns[$column_name] = $column_info;
            
            if ('order_status' === $column_name) {
                $new_columns['printify_status'] = __('Printify Status', 'wp-woocommerce-printify-sync');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate Printify column in orders list.
     * 
     * @param string $column
     * @param int $post_id
     * @return void
     */
    public function populatePrintifyOrderColumn(string $column, int $post_id): void
    {
        if ('printify_status' !== $column) {
            return;
        }
        
        $order = wc_get_order($post_id);
        $this->displayPrintifyOrderStatus($order);
    }
    
    /**
     * Populate Printify column in HPOS orders list.
     * 
     * @param string $column_id
     * @param \WC_Order $order
     * @return void
     */
    public function populateHPOSPrintifyOrderColumn(string $column_id, \WC_Order $order): void
    {
        if ('printify_status' !== $column_id) {
            return;
        }
        
        $this->displayPrintifyOrderStatus($order);
    }
    
    /**
     * Display Printify order status.
     * 
     * @param \WC_Order $order
     * @return void
     */
    private function displayPrintifyOrderStatus(\WC_Order $order): void
    {
        $printifyOrderId = HPOSCompatibility::getOrderMeta($order, '_wpwps_printify_order_id', true);
        $printifyStatus = HPOSCompatibility::getOrderMeta($order, '_wpwps_printify_order_status', true);
        
        if ($printifyOrderId) {
            echo '<span class="printify-status status-' . esc_attr($printifyStatus) . '">';
            echo esc_html(ucfirst($printifyStatus ?: 'pending'));
            echo '</span>';
            echo '<br><small>ID: ' . esc_html($printifyOrderId) . '</small>';
        } else {
            echo '<span class="printify-status status-not-sent">';
            echo esc_html__('Not Sent', 'wp-woocommerce-printify-sync');
            echo '</span>';
            echo '<br><button type="button" class="button send-to-printify" data-order-id="' . esc_attr($order->get_id()) . '">';
            echo esc_html__('Send to Printify', 'wp-woocommerce-printify-sync');
            echo '</button>';
        }
    }
    
    /**
     * Add Printify metabox to order details page.
     * 
     * @param \WC_Order $order
     * @return void
     */
    public function addPrintifyMetaboxToOrder(\WC_Order $order): void
    {
        $orderId = $order->get_id();
        $printifyOrderId = HPOSCompatibility::getOrderMeta($order, '_wpwps_printify_order_id', true);
        $printifyStatus = HPOSCompatibility::getOrderMeta($order, '_wpwps_printify_order_status', true);
        $trackingInfo = HPOSCompatibility::getOrderMeta($order, '_wpwps_tracking_info', true);
        
        ?>
        <div class="order_data_column">
            <h4><?php esc_html_e('Printify Information', 'wp-woocommerce-printify-sync'); ?></h4>
            <div class="address">
                <?php if ($printifyOrderId): ?>
                    <p>
                        <strong><?php esc_html_e('Printify Order ID:', 'wp-woocommerce-printify-sync'); ?></strong><br>
                        <?php echo esc_html($printifyOrderId); ?>
                    </p>
                    <p>
                        <strong><?php esc_html_e('Status:', 'wp-woocommerce-printify-sync'); ?></strong><br>
                        <span class="printify-status status-<?php echo esc_attr($printifyStatus); ?>">
                            <?php echo esc_html(ucfirst($printifyStatus ?: 'pending')); ?>
                        </span>
                    </p>
                    <?php if (!empty($trackingInfo) && is_array($trackingInfo)): ?>
                        <p>
                            <strong><?php esc_html_e('Tracking:', 'wp-woocommerce-printify-sync'); ?></strong><br>
                            <?php foreach ($trackingInfo as $tracking): ?>
                                <?php if (!empty($tracking['url'])): ?>
                                    <a href="<?php echo esc_url($tracking['url']); ?>" target="_blank">
                                        <?php echo esc_html($tracking['tracking_number']); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo esc_html($tracking['tracking_number']); ?>
                                <?php endif; ?>
                                <small>(<?php echo esc_html($tracking['carrier']); ?>)</small><br>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>
                    <p>
                        <button type="button" class="button sync-printify-order" data-order-id="<?php echo esc_attr($orderId); ?>">
                            <?php esc_html_e('Refresh Status', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </p>
                <?php else: ?>
                    <p>
                        <?php esc_html_e('This order has not been sent to Printify yet.', 'wp-woocommerce-printify-sync'); ?>
                    </p>
                    <p>
                        <button type="button" class="button send-to-printify" data-order-id="<?php echo esc_attr($orderId); ?>">
                            <?php esc_html_e('Send to Printify', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Add Printify fields to product.
     * 
     * @return void
     */
    public function addPrintifyProductFields(): void
    {
        global $post;
        
        echo '<div class="options_group">';
        
        woocommerce_wp_text_input([
            'id' => '_wpwps_printify_id',
            'label' => __('Printify ID', 'wp-woocommerce-printify-sync'),
            'desc_tip' => true,
            'description' => __('Enter the Printify product ID for synchronization.', 'wp-woocommerce-printify-sync'),
        ]);
        
        woocommerce_wp_text_input([
            'id' => '_wpwps_printify_external_id',
            'label' => __('Printify External ID', 'wp-woocommerce-printify-sync'),
            'desc_tip' => true,
            'description' => __('External ID used by Printify (auto-filled).', 'wp-woocommerce-printify-sync'),
            'custom_attributes' => ['readonly' => 'readonly'],
        ]);
        
        woocommerce_wp_text_input([
            'id' => '_wpwps_last_sync',
            'label' => __('Last Synchronized', 'wp-woocommerce-printify-sync'),
            'desc_tip' => true,
            'description' => __('Date and time of last synchronization with Printify.', 'wp-woocommerce-printify-sync'),
            'custom_attributes' => ['readonly' => 'readonly'],
        ]);
        
        echo '</div>';
    }
    
    /**
     * Save Printify product fields.
     * 
     * @param int $product_id
     * @return void
     */
    public function savePrintifyProductFields(int $product_id): void
    {
        $printify_id = isset($_POST['_wpwps_printify_id']) ? sanitize_text_field($_POST['_wpwps_printify_id']) : '';
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        // Get the previous ID to check if it changed
        $previousId = $product->get_meta('_wpwps_printify_id', true);
        
        // Update the product meta
        $product->update_meta_data('_wpwps_printify_id', $printify_id);
        $product->save();
        
        // If printify ID was updated, trigger a sync
        if ($previousId !== $printify_id && !empty($printify_id)) {
            try {
                $this->syncSingleProduct($product_id, $printify_id);
            } catch (\Exception $e) {
                // Log error but don't prevent saving
                error_log('Printify sync error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Synchronize a single product with Printify.
     * 
     * @param int $product_id
     * @param string $printify_id
     * @return bool
     */
    public function syncSingleProduct(int $product_id, string $printify_id): bool
    {
        $apiClient = $this->getApiClient();
        
        // Get the product data from Printify
        $endpoint = "shops/{$apiClient->getShopId()}/products/{$printify_id}.json";
        $productData = $apiClient->makeRequest($endpoint);
        
        if (!$productData) {
            return false;
        }
        
        // Process and update the WooCommerce product
        $result = $apiClient->processAndSaveProduct($productData, $product_id);
        
        return !empty($result);
    }
    
    /**
     * Handle order creation.
     * 
     * @param int $order_id
     * @param array $posted_data
     * @param \WC_Order $order
     * @return void
     */
    public function handleOrderCreation(int $order_id, array $posted_data, \WC_Order $order): void
    {
        // Auto-submit orders to Printify if the option is enabled
        $settings = get_option('wpwps_settings');
        $autoSubmit = isset($settings['auto_submit_orders']) && $settings['auto_submit_orders'] === 'yes';
        
        if ($autoSubmit) {
            $this->sendOrderToPrintify($order);
        }
    }
    
    /**
     * Handle order status change.
     * 
     * @param int $order_id
     * @param string $old_status
     * @param string $new_status
     * @param \WC_Order $order
     * @return void
     */
    public function handleOrderStatusChanged(int $order_id, string $old_status, string $new_status, \WC_Order $order): void
    {
        // Get settings
        $settings = get_option('wpwps_settings');
        $triggerStatus = isset($settings['submit_order_status']) ? $settings['submit_order_status'] : 'processing';
        
        // If new status matches trigger status and order isn't already sent to Printify
        if ($new_status === $triggerStatus) {
            $printifyOrderId = HPOSCompatibility::getOrderMeta($order, '_wpwps_printify_order_id', true);
            
            if (!$printifyOrderId) {
                $this->sendOrderToPrintify($order);
            }
        }
    }
    
    /**
     * Send order to Printify.
     * 
     * @param \WC_Order $order
     * @return bool
     */
    public function sendOrderToPrintify(\WC_Order $order): bool
    {
        try {
            $apiClient = $this->getApiClient();
            $orderId = $order->get_id();
            
            // Check if the order has already been submitted to Printify
            $printifyOrderId = HPOSCompatibility::getOrderMeta($order, '_wpwps_printify_order_id', true);
            if ($printifyOrderId) {
                $order->add_order_note(__('Order already submitted to Printify (ID: ' . $printifyOrderId . ')', 'wp-woocommerce-printify-sync'));
                return true;
            }
            
            // Check if order has Printify items
            $hasPrintifyItems = false;
            $printifyItems = [];
            
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $product = wc_get_product($product_id);
                
                if (!$product) {
                    continue;
                }
                
                $printifyId = $product->get_meta('_wpwps_printify_id', true);
                
                if ($printifyId) {
                    $hasPrintifyItems = true;
                    $variation_id = $item->get_variation_id();
                    $externalId = '';
                    
                    if ($variation_id) {
                        $variation = wc_get_product($variation_id);
                        if ($variation) {
                            $externalId = $variation->get_meta('_wpwps_printify_external_id', true);
                        }
                    }
                    
                    // Fallback to product external ID if variation doesn't have one
                    if (!$externalId) {
                        $externalId = $product->get_meta('_wpwps_printify_external_id', true);
                    }
                    
                    $printifyItems[] = [
                        'external_id' => $externalId,
                        'quantity' => $item->get_quantity(),
                    ];
                }
            }
            
            if (!$hasPrintifyItems) {
                $order->add_order_note(__('No Printify items found in this order.', 'wp-woocommerce-printify-sync'));
                return false;
            }
            
            // Get shipping address
            $shipping = [
                'first_name' => $order->get_shipping_first_name() ?: $order->get_billing_first_name(),
                'last_name' => $order->get_shipping_last_name() ?: $order->get_billing_last_name(),
                'address1' => $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
                'address2' => $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
                'city' => $order->get_shipping_city() ?: $order->get_billing_city(),
                'state' => $order->get_shipping_state() ?: $order->get_billing_state(),
                'zip' => $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
                'country' => $order->get_shipping_country() ?: $order->get_billing_country(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            ];
            
            // Prepare order data
            $orderData = [
                'external_id' => $order->get_order_number(),
                'label' => 'WooCommerce Order #' . $order->get_order_number(),
                'line_items' => $printifyItems,
                'shipping_method' => 'standard',
                'shipping_address' => $shipping,
                'send_shipping_notification' => true,
            ];
            
            // Send order to Printify
            $endpoint = "shops/{$apiClient->getShopId()}/orders.json";
            $response = $apiClient->makeRequest($endpoint, 'POST', $orderData);
            
            if (!$response || !isset($response['id'])) {
                $errorMessage = isset($response['message']) ? $response['message'] : 'Unknown error';
                $order->add_order_note(__('Failed to submit order to Printify: ', 'wp-woocommerce-printify-sync') . $errorMessage);
                return false;
            }
            
            // Save Printify order ID and status
            $printifyOrderId = $response['id'];
            HPOSCompatibility::updateOrderMeta($order, '_wpwps_printify_order_id', $printifyOrderId);
            HPOSCompatibility::updateOrderMeta($order, '_wpwps_printify_order_status', $response['status'] ?? 'pending');
            
            // Add note to the order
            $order->add_order_note(
                __('Order successfully submitted to Printify. ID: ', 'wp-woocommerce-printify-sync') . $printifyOrderId
            );
            
            return true;
        } catch (\Exception $e) {
            $order->add_order_note(__('Error submitting order to Printify: ', 'wp-woocommerce-printify-sync') . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get API client.
     * 
     * @return PrintifyClient
     */
    protected function getApiClient(): PrintifyClient
    {
        $settings = get_option('wpwps_settings');
        $apiKey = isset($settings['api_key']) ? $settings['api_key'] : '';
        $shopId = isset($settings['shop_id']) ? $settings['shop_id'] : '';
        
        return new PrintifyClient($apiKey, $shopId);
    }
}