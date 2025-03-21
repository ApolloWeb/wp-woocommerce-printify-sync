<?php
/**
 * Order Status Factory.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Orders
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

/**
 * Factory for creating order status definitions.
 */
class OrderStatusFactory {
    /**
     * Get pre-production statuses
     *
     * @return array
     */
    public function getPreProductionStatuses() {
        return [
            'awaiting-evidence' => 'Awaiting Customer Evidence',
            'submit-order' => 'Submit Order',
            'action-required' => 'Action Required',
            'on-hold' => 'On Hold',
        ];
    }

    /**
     * Get production statuses
     *
     * @return array
     */
    public function getProductionStatuses() {
        return [
            'in-production' => 'In Production',
            'has-issues' => 'Has Issues',
            'canceled-provider' => 'Canceled by Provider',
            'canceled-other' => 'Canceled (Various Reasons)',
        ];
    }

    /**
     * Get shipping statuses
     *
     * @return array
     */
    public function getShippingStatuses() {
        return [
            'ready-ship' => 'Ready to Ship',
            'shipped' => 'Shipped',
            'on-the-way' => 'On the Way',
            'available-pickup' => 'Available for Pickup',
            'out-delivery' => 'Out for Delivery',
            'delivery-attempt' => 'Delivery Attempt',
            'shipping-issue' => 'Shipping Issue',
            'return-sender' => 'Return to Sender',
            'delivered' => 'Delivered',
        ];
    }

    /**
     * Get refund and reprint statuses
     *
     * @return array
     */
    public function getRefundReprintStatuses() {
        return [
            'refund-awaiting-evidence' => 'Refund Awaiting Evidence',
            'refund-requested' => 'Refund Requested',
            'refund-approved' => 'Refund Approved',
            'refund-declined' => 'Refund Declined',
            'reprint-awaiting-evidence' => 'Reprint Awaiting Evidence',
            'reprint-requested' => 'Reprint Requested',
            'reprint-approved' => 'Reprint Approved',
            'reprint-declined' => 'Reprint Declined',
        ];
    }

    /**
     * Get all custom order statuses
     *
     * @return array
     */
    public function getAllStatuses() {
        return array_merge(
            $this->getPreProductionStatuses(),
            $this->getProductionStatuses(),
            $this->getShippingStatuses(),
            $this->getRefundReprintStatuses()
        );
    }
}
