<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce;

/**
 * Handles custom order statuses that mirror Printify's status system
 */
class CustomOrderStatuses
{
    /**
     * Printify status to WooCommerce status mapping
     */
    const PRINTIFY_TO_WC_STATUS_MAP = [
        'pending' => 'printify-pending',
        'on-hold' => 'printify-on-hold',
        'processing' => 'processing',
        'fulfillment' => 'printify-fulfillment',
        'ready-for-shipping' => 'printify-ready-shipping',
        'shipped' => 'completed',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded'
    ];

    /**
     * Register the custom order statuses
     */
    public function registerOrderStatuses()
    {
        register_post_status('wc-printify-pending', [
            'label' => _x('Printify Pending', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Pending <span class="count">(%s)</span>',
                'Printify Pending <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);

        register_post_status('wc-printify-on-hold', [
            'label' => _x('Printify On Hold', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify On Hold <span class="count">(%s)</span>',
                'Printify On Hold <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);

        register_post_status('wc-printify-fulfillment', [
            'label' => _x('Printify Fulfillment', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Fulfillment <span class="count">(%s)</span>',
                'Printify Fulfillment <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);

        register_post_status('wc-printify-ready-shipping', [
            'label' => _x('Ready for Shipping', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Ready for Shipping <span class="count">(%s)</span>',
                'Ready for Shipping <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
    }

    /**
     * Add custom statuses to WooCommerce order statuses list
     * 
     * @param array $order_statuses Existing order statuses
     * @return array Modified order statuses
     */
    public function addOrderStatusesToWooCommerce($order_statuses)
    {
        $new_order_statuses = [];

        // Add new order statuses after processing
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;

            if ($key === 'wc-processing') {
                $new_order_statuses['wc-printify-pending'] = _x('Printify Pending', 'Order status', 'wp-woocommerce-printify-sync');
                $new_order_statuses['wc-printify-on-hold'] = _x('Printify On Hold', 'Order status', 'wp-woocommerce-printify-sync');
                $new_order_statuses['wc-printify-fulfillment'] = _x('Printify Fulfillment', 'Order status', 'wp-woocommerce-printify-sync');
                $new_order_statuses['wc-printify-ready-shipping'] = _x('Ready for Shipping', 'Order status', 'wp-woocommerce-printify-sync');
            }
        }

        return $new_order_statuses;
    }

    /**
     * Convert a Printify status to WooCommerce status
     *
     * @param string $printifyStatus
     * @return string WooCommerce status
     */
    public function convertPrintifyStatusToWc($printifyStatus)
    {
        return self::PRINTIFY_TO_WC_STATUS_MAP[$printifyStatus] ?? 'processing';
    }

    /**
     * Convert a WooCommerce status to Printify status
     *
     * @param string $wcStatus
     * @return string|null Printify status or null if not mappable
     */
    public function convertWcStatusToPrintify($wcStatus)
    {
        // Remove 'wc-' prefix if present
        if (strpos($wcStatus, 'wc-') === 0) {
            $wcStatus = substr($wcStatus, 3);
        }

        // Find the Printify status that maps to this WC status
        return array_search($wcStatus, self::PRINTIFY_TO_WC_STATUS_MAP) ?: null;
    }
}
