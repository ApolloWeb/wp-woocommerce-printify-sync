<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Automattic\WooCommerce\Utilities\OrderUtil;

class OrderCommunicationService
{
    private ConfigService $config;
    private LoggerInterface $logger;
    private $currentTime = '2025-03-15 22:06:25';
    private $currentUser = 'ApolloWeb';

    public function __construct(
        ConfigService $config,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->initHooks();
    }

    private function initHooks(): void
    {
        // Order status notifications
        add_action('wpwps_send_shipping_notification', [$this, 'sendShippingNotification']);
        add_action('wpwps_send_delivery_notification', [$this, 'sendDeliveryNotification']);
        
        // Order notes
        add_action('wpwps_add_order_note', [$this, 'addOrderNote']);
        
        // Custom email notifications
        add_filter('woocommerce_email_classes', [$this, 'addCustomEmails']);
    }

    public function addOrderNote(int $orderId, string $note, string $type = 'internal'): void
    {
        try {
            $order = wc_get_order($orderId);
            if (!$order) {
                throw new \Exception("Order not found: {$orderId}");
            }

            $noteData = [
                'note' => $note,
                'is_customer_note' => $type === 'customer',
                'added_by' => $this->currentUser
            ];

            if (OrderUtil::custom_orders_table_usage_is_enabled()) {
                // HPOS compatible note addition
                $commentId = wc_get_order_note_id($order->add_order_note($noteData));
                
                // Add extra meta for HPOS
                if ($commentId) {
                    update_comment_meta($commentId, '_wpwps_note_type', $type);
                    update_comment_meta($commentId, '_wpwps_note_timestamp', $this->currentTime);
                }
            } else {
                // Legacy note addition
                $order->add_order_note($noteData);
            }

            $this->logger->info('Order note added', [
                'order_id' => $orderId,
                'type' => $type,
                'timestamp' => $this->currentTime
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to add order note', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendShippingNotification(\WC_Order $order): void
    {
        try {
            $trackingNumber = $order->get_meta('_printify_tracking_number');
            $carrier = $order->get_meta('_printify_tracking_carrier');
            $trackingUrl = $order->get_meta('_printify_tracking_url');

            // Add order note
            $note = sprintf(
                __('Order shipped via %s. Tracking number: %s', 'wp-woocommerce-printify-sync'),
                $carrier,
                $trackingNumber
            );
            
            $this->addOrderNote($order->get_id(), $note, 'customer');

            // Send email notification
            if ($this->config->get('send_shipping_emails', true)) {
                do_action('wpwps_shipped_notification', $order->get_id());
            }

            // Update order meta
            $order->update_meta_data('_printify_shipping_notification_sent', $this->currentTime);
            $order->save();

        } catch (\Exception $e) {
            $this->logger->error('Failed to send shipping notification', [
                'order_id' => $order->get_id(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendDeliveryNotification(\WC_Order $order): void
    {
        try {
            $note = __('Order has been delivered.', 'wp-woocommerce-printify-sync');
            $this->addOrderNote($order->get_id(), $note, 'customer');

            if ($this->config->get('send_delivery_emails', true)) {
                do_action('wpwps_delivered_notification', $order->get_id());
            }

            $order->update_meta_data('_printify_delivery_notification_sent', $this->currentTime);
            $order->save();

        } catch (\Exception $e) {
            $this->logger->error('Failed to send delivery notification', [
                'order_id' => $order->get_id(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function addCustomEmails(array $emails): array
    {
        $emails['WC_Email_Printify_Shipped'] = include(
            WPWPS_PLUGIN_PATH . 'src/Emails/OrderShippedEmail.php'
        );
        
        $emails['WC_Email_Printify_Delivered'] = include(
            WPWPS_PLUGIN_PATH . 'src/Emails/OrderDeliveredEmail.php'
        );

        return $emails;
    }
}