<?php
/**
 * Order statuses.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Orders
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

/**
 * OrderStatuses class.
 */
class OrderStatuses {
    /**
     * Order status factory.
     *
     * @var OrderStatusFactory
     */
    private $status_factory;

    /**
     * Order status mapper.
     *
     * @var OrderStatusMapperInterface
     */
    private $status_mapper;

    /**
     * Constructor.
     *
     * @param OrderStatusFactory        $status_factory Order status factory.
     * @param OrderStatusMapperInterface $status_mapper  Order status mapper.
     */
    public function __construct(
        OrderStatusFactory $status_factory,
        OrderStatusMapperInterface $status_mapper
    ) {
        $this->status_factory = $status_factory;
        $this->status_mapper = $status_mapper;
    }

    /**
     * Initialize order statuses.
     *
     * @return void
     */
    public function init() {
        // Register order statuses
        add_action('init', [$this, 'registerOrderStatuses']);
        
        // Add statuses to WooCommerce order statuses list
        add_filter('wc_order_statuses', [$this, 'addOrderStatuses']);
        
        // Add statuses to WooCommerce reports
        add_filter('woocommerce_reports_order_statuses', [$this, 'addReportOrderStatuses']);
        
        // Add custom statuses to bulk actions
        add_filter('bulk_actions-edit-shop_order', [$this, 'addBulkActions']);
    }

    /**
     * Register order statuses with WooCommerce.
     *
     * @return void
     */
    public function registerOrderStatuses() {
        foreach ($this->status_factory->getAllStatuses() as $slug => $label) {
            $this->registerStatus($slug, $label);
        }
    }

    /**
     * Register a single order status
     *
     * @param string $slug  Status slug
     * @param string $label Status label
     * @return void
     */
    private function registerStatus($slug, $label) {
        register_post_status('wc-' . $slug, [
            'label'                     => _x($label, 'Order status', 'wp-woocommerce-printify-sync'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync'),
        ]);
    }

    /**
     * Add order statuses to WooCommerce order statuses.
     *
     * @param array $order_statuses Existing order statuses.
     * @return array
     */
    public function addOrderStatuses($order_statuses) {
        $new_statuses = [];
        
        foreach ($this->status_factory->getAllStatuses() as $slug => $label) {
            $new_statuses['wc-' . $slug] = _x($label, 'Order status', 'wp-woocommerce-printify-sync');
        }

        // Insert new statuses after 'processing'
        $position = array_search('wc-processing', array_keys($order_statuses));
        
        if ($position !== false) {
            $position++;
            $order_statuses = array_slice($order_statuses, 0, $position, true) +
                $new_statuses +
                array_slice($order_statuses, $position, count($order_statuses) - $position, true);
        } else {
            $order_statuses = array_merge($order_statuses, $new_statuses);
        }

        return $order_statuses;
    }

    /**
     * Add order statuses to reports.
     *
     * @param array $statuses Order statuses.
     * @return array
     */
    public function addReportOrderStatuses($statuses) {
        // Add most important status slugs to reports
        $important_categories = array_merge(
            $this->status_factory->getPreProductionStatuses(),
            $this->status_factory->getProductionStatuses(),
            $this->status_factory->getShippingStatuses()
        );
        
        foreach ($important_categories as $slug => $label) {
            $statuses[] = $slug;
        }
        
        return $statuses;
    }

    /**
     * Add custom statuses to bulk actions.
     *
     * @param array $actions Bulk actions.
     * @return array
     */
    public function addBulkActions($actions) {
        $new_actions = [];
        
        // Only add the most commonly used statuses to avoid cluttering the dropdown
        $common_statuses = array_merge(
            $this->status_factory->getPreProductionStatuses(),
            [
                'in-production' => 'In Production',
                'has-issues' => 'Has Issues',
                'ready-ship' => 'Ready to Ship',
                'shipped' => 'Shipped',
                'on-the-way' => 'On the Way',
                'delivered' => 'Delivered',
                'refund-requested' => 'Refund Requested',
                'refund-approved' => 'Refund Approved',
                'reprint-requested' => 'Reprint Requested',
                'reprint-approved' => 'Reprint Approved',
            ]
        );
        
        foreach ($common_statuses as $slug => $label) {
            $new_actions['mark_' . $slug] = sprintf(
                __('Change status to %s', 'wp-woocommerce-printify-sync'),
                $label
            );
        }

        return array_merge($actions, $new_actions);
    }

    /**
     * Map Printify status to WooCommerce status.
     *
     * @param string $printify_status Printify order status.
     * @return string
     */
    public function mapPrintifyStatusToWooCommerce($printify_status) {
        return $this->status_mapper->mapToWooCommerce($printify_status);
    }
}
