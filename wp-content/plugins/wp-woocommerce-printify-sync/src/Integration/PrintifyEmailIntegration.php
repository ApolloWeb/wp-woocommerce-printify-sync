<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Integration;

class PrintifyEmailIntegration
{
    private $email_manager;

    public function __construct($email_manager)
    {
        $this->email_manager = $email_manager;
        $this->initHooks();
    }

    private function initHooks(): void
    {
        // Printify order status changes
        add_action('wpwps_printify_order_status_changed', [$this, 'handleOrderStatusChange'], 10, 3);
        
        // Printify sync events
        add_action('wpwps_printify_sync_failed', [$this, 'handleSyncFailure'], 10, 2);
        add_action('wpwps_printify_sync_success', [$this, 'handleSyncSuccess'], 10, 2);
        
        // Printify product events
        add_action('wpwps_printify_product_updated', [$this, 'handleProductUpdate'], 10, 2);
        add_action('wpwps_printify_product_error', [$this, 'handleProductError'], 10, 2);
    }

    public function handleOrderStatusChange(int $order_id, string $old_status, string $new_status): void
    {
        $ticket_id = $this->findOrCreateTicket($order_id, 'order_status');
        
        if (!$ticket_id) {
            return;
        }

        $status_data = [
            'order_id' => $order_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'printify_details' => $this->getPrintifyOrderDetails($order_id)
        ];

        // Trigger status email
        do_action('wpwps_ticket_status_change', $ticket_id, $status_data);
    }

    public function handleSyncFailure(int $entity_id, array $error_details): void
    {
        $ticket_id = $this->findOrCreateTicket($entity_id, 'sync_error');
        
        if (!$ticket_id) {
            return;
        }

        $note_data = [
            'content' => $this->formatSyncError($error_details),
            'is_error' => true,
            'error_details' => $error_details
        ];

        // Add error note to ticket
        do_action('wpwps_add_ticket_note', $ticket_id, $note_data);
    }

    public function handleSyncSuccess(int $entity_id, array $sync_details): void
    {
        $ticket_id = $this->findRelatedTicket($entity_id);
        
        if (!$ticket_id) {
            return;
        }

        $note_data = [
            'content' => $this->formatSyncSuccess($sync_details),
            'is_success' => true,
            'sync_details' => $sync_details
        ];

        // Add success note to ticket
        do_action('wpwps_add_ticket_note', $ticket_id, $note_data);
    }

    private function findOrCreateTicket(int $entity_id, string $type): ?int
    {
        // First try to find existing ticket
        $ticket_id = $this->findRelatedTicket($entity_id);
        
        if ($ticket_id) {
            return $ticket_id;
        }

        // Create new ticket if needed
        return $this->createAutoTicket($entity_id, $type);
    }

    private function findRelatedTicket(int $entity_id): ?int
    {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_related_entity_id' 
            AND meta_value = %d 
            LIMIT 1",
            $entity_id
        ));
    }

    private function createAutoTicket(int $entity_id, string $type): ?int
    {
        $ticket_data = [
            'post_title' => sprintf(
                __('Automated Ticket - %s #%d', 'wp-woocommerce-printify-sync'),
                ucfirst($type),
                $entity_id
            ),
            'post_type' => 'wpwps_ticket',
            'post_status' => 'publish',
        ];

        $ticket_id = wp_insert_post($ticket_data);

        if (!is_wp_error($ticket_id)) {
            update_post_meta($ticket_id, '_related_entity_id', $entity_id);
            update_post_meta($ticket_id, '_ticket_type', $type);
            return $ticket_id;
        }

        return null;
    }

    private function formatSyncError(array $error_details): string
    {
        return sprintf(
            __('Sync Error: %s\nError Code: %s\nDetails: %s', 'wp-woocommerce-printify-sync'),
            $error_details['message'] ?? 'Unknown error',
            $error_details['code'] ?? 'N/A',
            $error_details['details'] ?? 'No additional details'
        );
    }

    private function formatSyncSuccess(array $sync_details): string
    {
        return sprintf(
            __('Sync Completed Successfully\nSynced Items: %d\nUpdated: %s', 'wp-woocommerce-printify-sync'),
            $sync_details['items'] ?? 0,
            $sync_details['timestamp'] ?? current_time('mysql')
        );
    }

    private function getPrintifyOrderDetails(int $order_id): array
    {
        // Get Printify order details from the API
        // This is a placeholder - implement actual API call
        return [
            'printify_id' => get_post_meta($order_id, '_printify_order_id', true),
            'status' => get_post_meta($order_id, '_printify_status', true),
            'last_update' => get_post_meta($order_id, '_printify_last_update', true),
        ];
    }
}