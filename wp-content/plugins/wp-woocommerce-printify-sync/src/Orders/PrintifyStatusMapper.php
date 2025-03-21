<?php
/**
 * Printify Status Mapper.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Orders
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

/**
 * Maps Printify status codes to WooCommerce status codes.
 */
class PrintifyStatusMapper implements OrderStatusMapperInterface {
    /**
     * Get Printify status mapping.
     *
     * @return array
     */
    public function getStatusMapping() {
        return [
            // Pre-production statuses
            'awaiting_approval'         => 'awaiting-evidence',
            'pending'                   => 'submit-order',
            'action_required'           => 'action-required',
            'on_hold'                   => 'on-hold',
            
            // Production statuses
            'in_production'             => 'in-production',
            'production_issues'         => 'has-issues',
            'canceled_by_provider'      => 'canceled-provider',
            'canceled'                  => 'canceled-other',
            
            // Shipping statuses
            'ready_for_shipping'        => 'ready-ship',
            'shipped'                   => 'shipped',
            'in_transit'                => 'on-the-way',
            'ready_for_pickup'          => 'available-pickup',
            'out_for_delivery'          => 'out-delivery',
            'delivery_attempt'          => 'delivery-attempt',
            'shipping_issue'            => 'shipping-issue',
            'return_to_sender'          => 'return-sender',
            'delivered'                 => 'delivered',
            
            // Refund and reprint statuses
            'refund_awaiting_evidence'  => 'refund-awaiting-evidence',
            'refund_requested'          => 'refund-requested',
            'refund_approved'           => 'refund-approved',
            'refund_declined'           => 'refund-declined',
            'reprint_awaiting_evidence' => 'reprint-awaiting-evidence',
            'reprint_requested'         => 'reprint-requested',
            'reprint_approved'          => 'reprint-approved',
            'reprint_declined'          => 'reprint-declined',
            
            // Legacy mappings for backwards compatibility
            'processing'                => 'in-production',
            'fulfilled'                 => 'delivered',
            'cancelled'                 => 'canceled-other',
        ];
    }

    /**
     * Map Printify status to WooCommerce status.
     *
     * @param string $external_status Printify order status.
     * @return string
     */
    public function mapToWooCommerce($external_status) {
        $mapping = $this->getStatusMapping();
        $external_status = strtolower(str_replace(' ', '_', $external_status));
        
        if (isset($mapping[$external_status])) {
            return 'wc-' . $mapping[$external_status];
        }
        
        // Default to processing if no mapping found
        return 'wc-processing';
    }
}
