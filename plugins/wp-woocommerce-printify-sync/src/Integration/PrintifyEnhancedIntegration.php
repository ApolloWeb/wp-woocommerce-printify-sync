<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Integration;

use ApolloWeb\WPWooCommercePrintifySync\Email\EmailManager;
use WC_Order;

class PrintifyEnhancedIntegration
{
    private $email_manager;
    private $events_log;
    private $retry_handler;

    public function __construct(EmailManager $email_manager)
    {
        $this->email_manager = $email_manager;
        $this->initializeIntegration();
    }

    private function initializeIntegration(): void
    {
        // Production status events
        add_action('wpwps_printify_production_started', [$this, 'handleProductionStart'], 10, 2);
        add_action('wpwps_printify_production_completed', [$this, 'handleProductionComplete'], 10, 2);
        add_action('wpwps_printify_production_failed', [$this, 'handleProductionFailure'], 10, 2);

        // Shipping events
        add_action('wpwps_printify_shipping_label_created', [$this, 'handleShippingLabel'], 10, 2);
        add_action('wpwps_printify_order_shipped', [$this, 'handleOrderShipped'], 10, 2);
        add_action('wpwps_printify_tracking_updated', [$this, 'handleTrackingUpdate'], 10, 3);

        // Quality control events
        add_action('wpwps_printify_quality_check_failed', [$this, 'handleQualityCheckFail'], 10, 2);
        add_action('wpwps_printify_quality_check_passed', [$this, 'handleQualityCheckPass'], 10, 2);

        // Stock events
        add_action('wpwps_printify_stock_warning', [$this, 'handleStockWarning'], 10, 2);
        add_action('wpwps_printify_product_discontinued', [$this, 'handleProductDiscontinued'], 10, 2);
    }

    public function handleProductionStart(int $order_id, array $production_data): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Update order meta
        $order->update_meta_data('_printify_production_start', current_time('mysql'));
        $order->update_meta_data('_printify_production_batch', $production_data['batch_id'] ?? '');
        $order->save();

        // Create or update ticket
        $ticket_data = [
            'status' => 'in_production',
            'production_details' => $production_data,
            'estimated_completion' => $this->calculateEstimatedCompletion($production_data),
        ];

        $this->updateOrderTicket($order_id, 'production_start', $ticket_data);
    }

    public function handleProductionComplete(int $order_id, array $completion_data): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Update order status
        $order->update_meta_data('_printify_production_completed', current_time('mysql'));
        $order->update_meta_data('_printify_quality_score', $completion_data['quality_score'] ?? null);
        $order->save();

        // Notify customer
        $notification_data = [
            'production_time' => $this->calculateProductionTime($order),
            'quality_score' => $completion_data['quality_score'] ?? null,
            'next_steps' => $this->getNextStepsMessage($completion_data),
        ];

        $this->updateOrderTicket($order_id, 'production_complete', $notification_data);
    }

    public function handleShippingLabel(int $order_id, array $shipping_data): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Update shipping information
        $order->update_meta_data('_printify_shipping_label_created', current_time('mysql'));
        $order->update_meta_data('_printify_tracking_number', $shipping_data['tracking_number'] ?? '');
        $order->update_meta_data('_printify_carrier', $shipping_data['carrier'] ?? '');
        $order->save();

        // Prepare shipping notification
        $notification_data = [
            'carrier' => $shipping_data['carrier'] ?? '',
            'tracking_number' => $shipping_data['tracking_number'] ?? '',
            'estimated_delivery' => $shipping_data['estimated_delivery'] ?? '',
            'tracking_url' => $this->generateTrackingUrl($shipping_data),
        ];

        $this->updateOrderTicket($order_id, 'shipping_label_created', $notification_data);
    }

    public function handleTrackingUpdate(int $order_id, string $status, array $tracking_data): void
    {
        $significant_updates = ['in_transit', 'out_for_delivery', 'exception', 'delivered'];
        
        if (!in_array($status, $significant_updates)) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Update tracking history
        $tracking_history = $order->get_meta('_printify_tracking_history', true) ?: [];
        $tracking_history[] = [
            'status' => $status,
            'timestamp' => current_time('mysql'),
            'location' => $tracking_data['location'] ?? '',
            'details' => $tracking_data['details'] ?? '',
        ];

        $order->update_meta_data('_printify_tracking_history', $tracking_history);
        $order->update_meta_data('_printify_last_tracking_update', current_time('mysql'));
        $order->save();

        // Notify customer of significant updates
        $this->updateOrderTicket($order_id, 'tracking_update', [
            'status' => $status,
            'location' => $tracking_data['location'] ?? '',
            'details' => $tracking_data['details'] ?? '',
            'estimated_delivery' => $tracking_data['estimated_delivery'] ?? '',
        ]);
    }

    public function handleQualityCheckFail(int $order_id, array $quality_data): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Update order meta
        $order->update_meta_data('_printify_quality_check_failed', current_time('mysql'));
        $order->update_meta_data('_printify_quality_issues', $quality_data['issues'] ?? []);
        $order->save();

        // Create urgent ticket for customer service
        $ticket_data = [
            'priority' => 'high',
            'quality_issues' => $quality_data['issues'] ?? [],
            'resolution_options' => $quality_data['resolution_options'] ?? [],
            'requires_customer_input' => $quality_data['requires_customer_input'] ?? false,
        ];

        $this->createUrgentTicket($order_id, 'quality_check_failed', $ticket_data);
    }

    private function updateOrderTicket(int $order_id, string $event_type, array $data): void
    {
        $ticket_id = $this->findOrCreateTicket($order_id);
        if (!$ticket_id) {
            return;
        }

        // Add event note
        $note_data = [
            'type' => $event_type,
            'data' => $data,
            'timestamp' => current_time('mysql'),
        ];

        do_action('wpwps_add_ticket_note', $ticket_id, $note_data);

        // Send appropriate notification
        $this->sendEventNotification($ticket_id, $event_type, $data);
    }

    private function createUrgentTicket(int $order_id, string $event_type, array $data): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $ticket_data = [
            'post_title' => sprintf(
                __('Urgent: %s - Order #%s', 'wp-woocommerce-printify-sync'),
                ucfirst(str_replace('_', ' ', $event_type)),
                $order->get_order_number()
            ),
            'post_type' => 'wpwps_ticket',
            'post_status' => 'publish',
            'meta_input' => [
                '_ticket_priority' => 'high',
                '_related_order_id' => $order_id,
                '_event_type' => $event_type,
                '_event_data' => $data,
            ],
        ];

        $ticket_id = wp_insert_post($ticket_data);

        if (!is_wp_error($ticket_id)) {
            // Notify customer service team
            do_action('wpwps_urgent_ticket_created', $ticket_id, $event_type, $data);
        }
    }

    private function calculateEstimatedCompletion(array $production_data): string
    {
        $base_time = strtotime('now');
        $production_days = $production_data['estimated_days'] ?? 3;
        
        return date('Y-m-d H:i:s', strtotime("+{$production_days} weekdays", $base_time));
    }

    private function calculateProductionTime(WC_Order $order): string
    {
        $start = $order->get_meta('_printify_production_start');
        $end = $order->get_meta('_printify_production_completed');

        if (!$start || !$end) {
            return '0';
        }

        $start_time = strtotime($start);
        $end_time = strtotime($end);

        return human_time_diff($start_time, $end_time);
    }

    private function generateTrackingUrl(array $shipping_data): string
    {
        $carriers = [
            'usps' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=',
            'fedex' => 'https://www.fedex.com/fedextrack/?trknbr=',
            'ups' => 'https://www.ups.com/track?tracknum=',
            'dhl' => 'https://www.dhl.com/en/express/tracking.html?AWB=',
        ];

        $carrier = strtolower($shipping_data['carrier'] ?? '');
        $tracking = $shipping_data['tracking_number'] ?? '';

        return isset($carriers[$carrier]) ? $carriers[$carrier] . $tracking : '';
    }

    private function getNextStepsMessage(array $completion_data): string
    {
        $messages = [
            'shipping_pending' => __('Your order will be shipped soon. We\'ll notify you when it\'s on its way.', 'wp-woocommerce-printify-sync'),
            'quality_check' => __('Your order is undergoing final quality checks before shipping.', 'wp-woocommerce-printify-sync'),
            'ready_to_ship' => __('Your order is packaged and ready to ship. Expect tracking information within 24 hours.', 'wp-woocommerce-printify-sync'),
        ];

        return $messages[$completion_data['status'] ?? 'shipping_pending'] ?? $messages['shipping_pending'];
    }
}