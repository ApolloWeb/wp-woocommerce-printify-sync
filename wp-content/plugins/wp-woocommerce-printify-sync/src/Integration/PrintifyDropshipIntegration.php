<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Integration;

use ApolloWeb\WPWooCommercePrintifySync\Email\EmailManager;
use WC_Order;

class PrintifyDropshipIntegration
{
    private $email_manager;
    private $printify_api;

    public function __construct(EmailManager $email_manager)
    {
        $this->email_manager = $email_manager;
        $this->initializeIntegration();
    }

    private function initializeIntegration(): void
    {
        // Order fulfillment events
        add_action('wpwps_printify_order_submitted', [$this, 'handleOrderSubmission'], 10, 2);
        add_action('wpwps_printify_order_accepted', [$this, 'handleOrderAccepted'], 10, 2);
        add_action('wpwps_printify_fulfillment_started', [$this, 'handleFulfillmentStart'], 10, 2);
        add_action('wpwps_printify_fulfillment_completed', [$this, 'handleFulfillmentComplete'], 10, 2);
        add_action('wpwps_printify_fulfillment_failed', [$this, 'handleFulfillmentFailure'], 10, 2);

        // Product sync events
        add_action('wpwps_printify_product_sync_started', [$this, 'handleProductSyncStart'], 10, 2);
        add_action('wpwps_printify_product_sync_completed', [$this, 'handleProductSyncComplete'], 10, 2);
        add_action('wpwps_printify_product_sync_failed', [$this, 'handleProductSyncFailure'], 10, 2);

        // Inventory events
        add_action('wpwps_printify_inventory_warning', [$this, 'handleInventoryWarning'], 10, 2);
        add_action('wpwps_printify_product_unavailable', [$this, 'handleProductUnavailable'], 10, 2);
    }

    public function handleOrderSubmission(int $order_id, array $submission_data): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Update order meta
        $order->update_meta_data('_printify_submission_date', current_time('mysql'));
        $order->update_meta_data('_printify_order_id', $submission_data['printify_id'] ?? '');
        $order->save();

        // Create fulfillment tracking record
        $this->createFulfillmentRecord($order_id, [
            'status' => 'submitted',
            'printify_id' => $submission_data['printify_id'] ?? '',
            'submission_details' => $submission_data,
        ]);

        // Notify admin of successful submission
        $this->notifyAdmin('order_submitted', $order_id, $submission_data);
    }

    public function handleOrderAccepted(int $order_id, array $acceptance_data): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Update order meta
        $order->update_meta_data('_printify_acceptance_date', current_time('mysql'));
        $order->update_meta_data('_printify_estimated_completion', $this->calculateEstimatedCompletion($acceptance_data));
        $order->save();

        // Update fulfillment record
        $this->updateFulfillmentRecord($order_id, 'accepted', [
            'estimated_completion' => $this->calculateEstimatedCompletion($acceptance_data),
            'acceptance_details' => $acceptance_data,
        ]);

        // Notify customer of order acceptance
        $this->notifyCustomer($order_id, 'order_accepted', [
            'estimated_completion' => $this->calculateEstimatedCompletion($acceptance_data),
            'order_details' => $this->getOrderSummary($order),
        ]);
    }

    public function handleFulfillmentComplete(int $order_id, array $fulfillment_data): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Update order status
        $order->update_status('completed', __('Order fulfilled by Printify', 'wp-woocommerce-printify-sync'));
        $order->update_meta_data('_printify_fulfillment_date', current_time('mysql'));
        $order->update_meta_data('_printify_tracking_info', $fulfillment_data['tracking'] ?? []);
        $order->save();

        // Update fulfillment record
        $this->updateFulfillmentRecord($order_id, 'completed', [
            'completion_date' => current_time('mysql'),
            'tracking_info' => $fulfillment_data['tracking'] ?? [],
            'fulfillment_details' => $fulfillment_data,
        ]);

        // Notify customer of fulfillment completion
        $this->notifyCustomer($order_id, 'fulfillment_complete', [
            'tracking_info' => $fulfillment_data['tracking'] ?? [],
            'order_details' => $this->getOrderSummary($order),
        ]);
    }

    public function handleFulfillmentFailure(int $order_id, array $error_data): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Update fulfillment record
        $this->updateFulfillmentRecord($order_id, 'failed', [
            'error_date' => current_time('mysql'),
            'error_details' => $error_data,
        ]);

        // Create urgent ticket for admin
        $this->createUrgentTicket($order_id, 'fulfillment_failed', [
            'error_details' => $error_data,
            'order_details' => $this->getOrderSummary($order),
        ]);

        // Notify admin of failure
        $this->notifyAdmin('fulfillment_failed', $order_id, $error_data);
    }

    public function handleProductSyncStart(int $batch_id, array $sync_data): void
    {
        update_option('wpwps_last_sync_start', current_time('mysql'));
        update_option('wpwps_current_sync_batch', $batch_id);
        update_option('wpwps_sync_products_total', $sync_data['total'] ?? 0);
        update_option('wpwps_sync_status', 'in_progress');

        // Log sync start
        $this->logSyncEvent('start', [
            'batch_id' => $batch_id,
            'total_products' => $sync_data['total'] ?? 0,
            'start_time' => current_time('mysql'),
        ]);
    }

    public function handleProductSyncComplete(int $batch_id, array $sync_results): void
    {
        update_option('wpwps_last_sync_complete', current_time('mysql'));
        update_option('wpwps_sync_status', 'completed');
        delete_option('wpwps_current_sync_batch');

        // Log sync completion
        $this->logSyncEvent('complete', [
            'batch_id' => $batch_id,
            'products_synced' => $sync_results['synced'] ?? 0,
            'products_failed' => $sync_results['failed'] ?? 0,
            'completion_time' => current_time('mysql'),
        ]);

        // Notify admin of sync completion
        $this->notifyAdmin('sync_complete', $batch_id, $sync_results);
    }

    private function createFulfillmentRecord(int $order_id, array $data): void
    {
        $record = [
            'order_id' => $order_id,
            'status' => $data['status'],
            'printify_id' => $data['printify_id'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'details' => wp_json_encode($data),
        ];

        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}wpwps_fulfillment_tracking", $record);
    }

    private function updateFulfillmentRecord(int $order_id, string $status, array $data): void
    {
        global $wpdb;
        
        $wpdb->update(
            "{$wpdb->prefix}wpwps_fulfillment_tracking",
            [
                'status' => $status,
                'updated_at' => current_time('mysql'),
                'details' => wp_json_encode($data),
            ],
            ['order_id' => $order_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    private function calculateEstimatedCompletion(array $data): string
    {
        $base_time = strtotime('now');
        $production_days = $data['estimated_days'] ?? 3;
        
        return date('Y-m-d H:i:s', strtotime("+{$production_days} weekdays", $base_time));
    }

    private function getOrderSummary(WC_Order $order): array
    {
        return [
            'order_number' => $order->get_order_number(),
            'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'items' => array_map(function($item) {
                return [
                    'name' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'printify_id' => $item->get_meta('_printify_product_id'),
                ];
            }, $order->get_items()),
            'printify_id' => $order->get_meta('_printify_order_id'),
        ];
    }

    private function logSyncEvent(string $type, array $data): void
    {
        global $wpdb;
        
        $wpdb->insert(
            "{$wpdb->prefix}wpwps_sync_log",
            [
                'event_type' => $type,
                'event_data' => wp_json_encode($data),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s']
        );
    }

    private function notifyAdmin(string $event_type, $entity_id, array $data): void
    {
        $admin_email = get_option('admin_email');
        $this->email_manager->sendAdminNotification($admin_email, $event_type, $entity_id, $data);
    }

    private function notifyCustomer(int $order_id, string $event_type, array $data): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->email_manager->sendCustomerNotification(
            $order->get_billing_email(),
            $event_type,
            $order_id,
            $data
        );
    }
}