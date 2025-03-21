<?php
/**
 * Order Controller.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderSync;
use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActionSchedulerService;

/**
 * Order Controller class.
 */
class OrderController
{
    /**
     * Order sync service.
     *
     * @var OrderSync
     */
    private $order_sync;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Action Scheduler service.
     *
     * @var ActionSchedulerService
     */
    private $action_scheduler;

    /**
     * Constructor.
     *
     * @param OrderSync            $order_sync      Order sync service.
     * @param Logger               $logger          Logger instance.
     * @param ActionSchedulerService $action_scheduler Action Scheduler service.
     */
    public function __construct(OrderSync $order_sync, Logger $logger, ActionSchedulerService $action_scheduler)
    {
        $this->order_sync = $order_sync;
        $this->logger = $logger;
        $this->action_scheduler = $action_scheduler;
    }

    /**
     * Initialize the controller.
     *
     * @return void
     */
    public function init()
    {
        add_action('wp_ajax_wpwps_sync_all_orders', [$this, 'syncAllOrders']);
        add_action('wp_ajax_wpwps_sync_single_order', [$this, 'syncSingleOrder']);
        add_action('wp_ajax_wpwps_get_orders', [$this, 'getOrders']);
        add_action('wp_ajax_wpwps_get_order_details', [$this, 'getOrderDetails']);
    }

    /**
     * Sync all orders from Printify.
     *
     * @return void
     */
    public function syncAllOrders()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $this->logger->info('Starting order sync from admin request');
        $action_id = $this->action_scheduler->scheduleSyncAllOrders();

        if ($action_id) {
            wp_send_json_success([
                'message' => __('Orders sync has been scheduled successfully. It will run in the background.', 'wp-woocommerce-printify-sync'),
                'action_id' => $action_id,
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to schedule orders sync.', 'wp-woocommerce-printify-sync'),
            ]);
        }
    }

    /**
     * Sync a single order from Printify.
     *
     * @return void
     */
    public function syncSingleOrder()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $order_id = isset($_POST['order_id']) ? sanitize_text_field(wp_unslash($_POST['order_id'])) : '';

        if (empty($order_id)) {
            wp_send_json_error([
                'message' => __('Order ID is required.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $this->logger->info("Syncing order {$order_id} from admin request");
        $result = $this->order_sync->syncSingleOrder($order_id);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
            return;
        }

        wp_send_json_success([
            'message' => $result['message'],
            'order_id' => $result['order_id'],
        ]);
    }

    /**
     * Get orders for display in the admin.
     *
     * @return void
     */
    public function getOrders()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $page = isset($_GET['page']) ? absint($_GET['page']) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $per_page = 10;

        // Build WP_Query args
        $args = [
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [],
        ];

        // Add filter for Printify orders only
        $args['meta_query'][] = [
            'relation' => 'OR',
            [
                'key' => '_printify_order_id',
                'compare' => 'EXISTS',
            ],
            [
                'key' => '_printify_product_id',
                'compare' => 'EXISTS',
            ],
        ];

        // Add status filter if specified
        if (!empty($status)) {
            if ($status === 'wc-processing') {
                $status = 'processing';
            } elseif ($status === 'wc-completed') {
                $status = 'completed';
            } elseif ($status === 'wc-on-hold') {
                $status = 'on-hold';
            } elseif ($status === 'wc-cancelled') {
                $status = 'cancelled';
            } elseif ($status === 'wc-refunded') {
                $status = 'refunded';
            }
            
            $args['post_status'] = 'wc-' . $status;
        }

        // Add search filter if specified
        if (!empty($search)) {
            // Search in post ID, post title, or meta values
            $args['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key' => '_billing_first_name',
                    'value' => $search,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => '_billing_last_name',
                    'value' => $search,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => '_billing_email',
                    'value' => $search,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => '_printify_order_id',
                    'value' => $search,
                    'compare' => 'LIKE',
                ],
            ];

            // Also search by order ID
            if (is_numeric($search)) {
                $args['p'] = absint($search);
            }
        }

        // Get orders
        $query = new \WP_Query($args);
        $orders = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $order_id = get_the_ID();
                $order = wc_get_order($order_id);

                if (!$order) {
                    continue;
                }

                // Get order data
                $orders[] = $this->formatOrderForDisplay($order);
            }
        }

        // Restore original post data
        wp_reset_postdata();

        // Build pagination data
        $total_orders = $query->found_posts;
        $total_pages = ceil($total_orders / $per_page);

        $pagination = [
            'total_orders' => $total_orders,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'per_page' => $per_page,
            'showing_text' => sprintf(
                /* translators: %1$d: first result, %2$d: last result, %3$d: total results */
                __('Showing %1$d to %2$d of %3$d orders', 'wp-woocommerce-printify-sync'),
                (($page - 1) * $per_page) + 1,
                min($page * $per_page, $total_orders),
                $total_orders
            ),
        ];

        wp_send_json_success([
            'orders' => $orders,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Get order details for display in the admin.
     *
     * @return void
     */
    public function getOrderDetails()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        $order_id = isset($_GET['order_id']) ? sanitize_text_field(wp_unslash($_GET['order_id'])) : '';

        if (empty($order_id)) {
            wp_send_json_error([
                'message' => __('Order ID is required.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        // Try to get order by Printify ID first
        $wc_order_id = $this->getWooCommerceOrderIdByPrintifyId($order_id);

        // If not found, try to get order by WooCommerce ID
        if (!$wc_order_id && is_numeric($order_id)) {
            $wc_order_id = absint($order_id);
        }

        // Get the order
        $order = wc_get_order($wc_order_id);

        if (!$order) {
            wp_send_json_error([
                'message' => __('Order not found.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }

        // Get detailed order data
        $order_data = $this->formatOrderDetailsForDisplay($order);

        wp_send_json_success([
            'order' => $order_data,
        ]);
    }

    /**
     * Format order data for display in the admin.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return array Formatted order data.
     */
    private function formatOrderForDisplay($order)
    {
        $order_id = $order->get_id();
        $printify_order_id = $order->get_meta('_printify_order_id', true);
        $last_synced = $order->get_meta('_printify_last_synced', true);
        
        // Format last synced date
        if ($last_synced) {
            $last_synced = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_synced));
        }

        // Get customer name
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $customer = trim($first_name . ' ' . $last_name);
        
        if (empty($customer)) {
            $customer = $order->get_billing_email();
            
            if (empty($customer)) {
                $customer = __('Guest', 'wp-woocommerce-printify-sync');
            }
        }

        // Get order items count
        $items = $order->get_items();
        $product_count = count($items);
        $product_names = [];
        
        foreach ($items as $item) {
            $product_names[] = $item->get_name() . ' &times; ' . $item->get_quantity();
            
            if (count($product_names) >= 2) {
                break;
            }
        }
        
        $products = implode('<br>', $product_names);
        
        if (count($items) > 2) {
            $products .= '<br><small>' . sprintf(__('+ %d more', 'wp-woocommerce-printify-sync'), count($items) - 2) . '</small>';
        }

        return [
            'id' => $order_id,
            'number' => $order->get_order_number(),
            'printify_id' => $printify_order_id,
            'date' => $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format')) : '',
            'status' => $order->get_status(),
            'customer' => $customer,
            'products' => $products,
            'total' => $order->get_formatted_order_total(),
            'last_synced' => $last_synced,
            'edit_url' => get_edit_post_link($order_id),
        ];
    }

    /**
     * Format detailed order data for display in the admin.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return array Formatted order data.
     */
    private function formatOrderDetailsForDisplay($order)
    {
        $order_id = $order->get_id();
        $printify_order_id = $order->get_meta('_printify_order_id', true);
        $last_synced = $order->get_meta('_printify_last_synced', true);
        
        // Format last synced date
        if ($last_synced) {
            $last_synced = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_synced));
        }

        // Get customer data
        $customer = [
            'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
        ];

        // Get billing address
        $billing = [
            'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'company' => $order->get_billing_company(),
            'address_1' => $order->get_billing_address_1(),
            'address_2' => $order->get_billing_address_2(),
            'city' => $order->get_billing_city(),
            'state' => $order->get_billing_state(),
            'postcode' => $order->get_billing_postcode(),
            'country' => $order->get_billing_country(),
        ];

        // Get shipping address
        $shipping = [
            'name' => trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()),
            'company' => $order->get_shipping_company(),
            'address_1' => $order->get_shipping_address_1(),
            'address_2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'state' => $order->get_shipping_state(),
            'postcode' => $order->get_shipping_postcode(),
            'country' => $order->get_shipping_country(),
        ];

        // If shipping name is empty, use billing name
        if (empty($shipping['name'])) {
            $shipping['name'] = $billing['name'];
        }

        // Get line items
        $items = $order->get_items();
        $line_items = [];

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $product = $item->get_product();
            
            $item_data = [
                'id' => $item->get_id(),
                'name' => $item->get_name(),
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'sku' => $product ? $product->get_sku() : '',
                'quantity' => $item->get_quantity(),
                'price' => wc_price($order->get_item_subtotal($item, false, false)),
                'total' => wc_price($order->get_line_subtotal($item, false, false)),
                'meta' => [],
            ];

            // Get item meta
            $meta_data = $item->get_meta_data();
            
            foreach ($meta_data as $meta) {
                // Skip internal meta
                if (substr($meta->key, 0, 1) === '_') {
                    continue;
                }
                
                $item_data['meta'][] = [
                    'key' => wc_attribute_label($meta->key, $product),
                    'value' => $meta->value,
                ];
            }

            $line_items[] = $item_data;
        }

        // Get order totals
        $totals = [
            'subtotal' => wc_price($order->get_subtotal()),
            'shipping' => wc_price($order->get_shipping_total()),
            'tax' => wc_price($order->get_total_tax()),
            'discount' => wc_price($order->get_discount_total()),
            'total' => wc_price($order->get_total()),
        ];

        // Get tracking information
        $tracking_info = $this->getOrderTrackingInfo($order);

        return [
            'id' => $order_id,
            'number' => $order->get_order_number(),
            'printify_id' => $printify_order_id,
            'date' => $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format') . ' ' . get_option('time_format')) : '',
            'status' => $order->get_status(),
            'customer' => $customer,
            'billing' => $billing,
            'shipping' => $shipping,
            'line_items' => $line_items,
            'totals' => $totals,
            'tracking_info' => $tracking_info,
            'last_synced' => $last_synced,
            'edit_url' => get_edit_post_link($order_id),
        ];
    }

    /**
     * Get tracking information for an order.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return array Tracking information.
     */
    private function getOrderTrackingInfo($order)
    {
        $tracking_info = [];
        $meta_data = $order->get_meta_data();
        
        foreach ($meta_data as $meta) {
            $key = $meta->key;
            
            if (strpos($key, '_printify_tracking_') === 0) {
                $tracking_data = maybe_unserialize($meta->value);
                
                if (is_array($tracking_data) && isset($tracking_data['carrier']) && isset($tracking_data['tracking_number'])) {
                    $tracking_info[] = $tracking_data;
                }
            }
        }
        
        return $tracking_info;
    }

    /**
     * Get WooCommerce order ID by Printify order ID.
     *
     * @param string $printify_order_id Printify order ID.
     * @return int|false WooCommerce order ID or false if not found.
     */
    private function getWooCommerceOrderIdByPrintifyId($printify_order_id)
    {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_printify_order_id' AND meta_value = %s LIMIT 1",
            $printify_order_id
        );
        
        $order_id = $wpdb->get_var($query);
        
        return $order_id ? (int) $order_id : false;
    }
}
