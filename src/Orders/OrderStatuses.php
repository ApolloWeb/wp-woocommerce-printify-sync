<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

class OrderStatuses {
    // Pre-production statuses
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_AWAITING_EVIDENCE = 'awaiting_evidence';
    const STATUS_SUBMIT_ORDER = 'submit_order';
    const STATUS_ACTION_REQUIRED = 'action_required';

    // Production statuses
    const STATUS_IN_PRODUCTION = 'in_production';
    const STATUS_HAS_ISSUES = 'has_issues';
    const STATUS_CANCELED_PROVIDER = 'canceled_provider';
    const STATUS_CANCELED = 'canceled';

    // Shipping statuses
    const STATUS_READY_TO_SHIP = 'ready_to_ship';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_ON_THE_WAY = 'on_the_way';
    const STATUS_AVAILABLE_PICKUP = 'available_pickup';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERY_ATTEMPT = 'delivery_attempt';
    const STATUS_SHIPPING_ISSUE = 'shipping_issue';
    const STATUS_RETURN_SENDER = 'return_sender';
    const STATUS_DELIVERED = 'delivered';

    // Refund statuses
    const STATUS_REFUND_AWAITING_EVIDENCE = 'refund_awaiting_evidence';
    const STATUS_REFUND_REQUESTED = 'refund_requested';
    const STATUS_REFUND_APPROVED = 'refund_approved';
    const STATUS_REFUND_DECLINED = 'refund_declined';

    // Reprint statuses
    const STATUS_REPRINT_AWAITING_EVIDENCE = 'reprint_awaiting_evidence';
    const STATUS_REPRINT_REQUESTED = 'reprint_requested';
    const STATUS_REPRINT_APPROVED = 'reprint_approved';
    const STATUS_REPRINT_DECLINED = 'reprint_declined';

    const STATUS_PENDING_APPROVAL = 'pending-approval';
    const STATUS_PRODUCTION_PENDING = 'production-pending';
    const STATUS_PRODUCTION_QUEUED = 'production-queued';
    const STATUS_PRODUCTION_STARTED = 'production-started';
    const STATUS_REPRINT_REQUESTED = 'reprint-requested';
    const STATUS_REFUND_REQUESTED = 'refund-requested';

    public function init() {
        add_action('init', [$this, 'registerOrderStatuses']);
        add_filter('wc_order_statuses', [$this, 'addOrderStatuses']);
        add_action('woocommerce_order_status_changed', [$this, 'handleStatusChange'], 10, 4);
    }

    public function registerOrderStatuses() {
        register_post_status(self::STATUS_PENDING_APPROVAL, [
            'label' => __('Pending Approval', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Pending Approval <span class="count">(%s)</span>', 
                                   'Pending Approval <span class="count">(%s)</span>')
        ]);

        // Register other statuses...
    }

    public function addOrderStatuses($order_statuses) {
        $new_statuses = [
            'wc-' . self::STATUS_PENDING_APPROVAL => __('Pending Approval', 'wp-woocommerce-printify-sync'),
            'wc-' . self::STATUS_PRODUCTION_PENDING => __('Production Pending', 'wp-woocommerce-printify-sync'),
            'wc-' . self::STATUS_PRODUCTION_QUEUED => __('Production Queued', 'wp-woocommerce-printify-sync'),
            'wc-' . self::STATUS_PRODUCTION_STARTED => __('In Production', 'wp-woocommerce-printify-sync'),
            'wc-' . self::STATUS_SHIPPED => __('Shipped', 'wp-woocommerce-printify-sync'),
            'wc-' . self::STATUS_REPRINT_REQUESTED => __('Reprint Requested', 'wp-woocommerce-printify-sync'),
            'wc-' . self::STATUS_REFUND_REQUESTED => __('Refund Requested', 'wp-woocommerce-printify-sync')
        ];

        return array_merge($order_statuses, $new_statuses);
    }

    public static function getStatusGroups() {
        return [
            'pre_production' => [
                self::STATUS_ON_HOLD,
                self::STATUS_AWAITING_EVIDENCE,
                self::STATUS_SUBMIT_ORDER,
                self::STATUS_ACTION_REQUIRED
            ],
            'production' => [
                self::STATUS_IN_PRODUCTION,
                self::STATUS_HAS_ISSUES,
                self::STATUS_CANCELED_PROVIDER,
                self::STATUS_CANCELED
            ],
            // ...existing code...
        ];
    }

    public static function getStatusLabel($status) {
        $labels = [
            self::STATUS_ON_HOLD => __('On Hold', 'wp-woocommerce-printify-sync'),
            self::STATUS_AWAITING_EVIDENCE => __('Awaiting Customer Evidence', 'wp-woocommerce-printify-sync'),
            // ...existing code...
        ];
        
        return $labels[$status] ?? $status;
    }
}
