<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class NotificationService
{
    private $emailTemplate;
    private $logger;

    public function __construct(
        EmailTemplateService $emailTemplate,
        LoggerInterface $logger
    ) {
        $this->emailTemplate = $emailTemplate;
        $this->logger = $logger;
    }

    public function register(): void
    {
        add_action('wpwps_ticket_created', [$this, 'sendTicketCreatedNotification'], 10, 2);
        add_action('wpwps_ticket_updated', [$this, 'sendTicketUpdatedNotification'], 10, 2);
        add_action('wpwps_ticket_replied', [$this, 'sendTicketReplyNotification'], 10, 2);
        add_action('wpwps_ticket_resolved', [$this, 'sendTicketResolvedNotification'], 10, 2);
    }

    public function sendTicketCreatedNotification(int $ticketId, array $data): void
    {
        try {
            $this->emailTemplate->sendTicketEmail($ticketId, 'ticket_created', [
                'customer_name' => $this->getCustomerName($ticketId),
                'ticket_data' => $data
            ]);

            $this->logNotification($ticketId, 'created');
        } catch (\Exception $e) {
            $this->logger->error('Failed to send ticket creation notification', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendTicketUpdatedNotification(int $ticketId, array $data): void
    {
        try {
            $this->emailTemplate->sendTicketEmail($ticketId, 'ticket_updated', [
                'customer_name' => $this->getCustomerName($ticketId),
                'ticket_data' => $data,
                'update_details' => $this->getUpdateDetails($data)
            ]);

            $this->logNotification($ticketId, 'updated');
        } catch (\Exception $e) {
            $this->logger->error('Failed to send ticket update notification', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getCustomerName(int $ticketId): string
    {
        $customerId = get_post_meta($ticketId, '_customer_id', true);
        if ($customerId) {
            $customer = new \WC_Customer($customerId);
            return $customer->get_first_name() ?: $customer->get_display_name();
        }

        return __('Customer', 'wp-woocommerce-printify-sync');
    }

    private function getUpdateDetails(array $data): array
    {
        $details = [];

        if (!empty($data['status_changed'])) {
            $details[] = sprintf(
                __('Status changed to: %s', 'wp-woocommerce-printify-sync'),
                $data['new_status']
            );
        }

        if (!empty($data['priority_changed'])) {
            $details[] = sprintf(
                __('Priority updated to: %s', 'wp-woocommerce-printify-sync'),
                $data['new_priority']
            );
        }

        return $details;
    }

    private function logNotification(int $ticketId, string $type): void
    {
        $history = get_post_meta($ticketId, '_notification_history', true) ?: [];
        
        $history[] = [
            'type' => $type,
            'timestamp' => current_time('mysql'),
            'success' => true
        ];

        update_post_meta($ticketId, '_notification_history', $history);
    }
}