<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class EmailService {
    private $job_service;
    private $logger_service;
    private $queue_table = 'wpwps_email_queue';

    public function __construct() {
        $this->job_service = new JobService();
        $this->logger_service = new LoggerService();
        add_action('init', [$this, 'createQueueTable']);
        add_action('wpwps_process_email_queue', [$this, 'processQueue']);
        add_action('admin_init', [$this, 'scheduleQueueProcessing']);
    }

    public function createQueueTable(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . $this->queue_table;

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            to_email varchar(100) NOT NULL,
            subject varchar(255) NOT NULL,
            message longtext NOT NULL,
            headers text,
            attachments longtext,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            scheduled_for datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            attempts int(11) NOT NULL DEFAULT 0,
            last_attempt datetime,
            error_message text,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY scheduled_for (scheduled_for)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function queueEmail(string $to, string $subject, string $message, array $headers = [], array $attachments = [], ?string $scheduled_for = null): bool {
        global $wpdb;

        $data = [
            'to_email' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => maybe_serialize($headers),
            'attachments' => maybe_serialize($attachments),
            'scheduled_for' => $scheduled_for ?? current_time('mysql')
        ];

        return $wpdb->insert(
            $wpdb->prefix . $this->queue_table,
            $data,
            ['%s', '%s', '%s', '%s', '%s', '%s']
        ) !== false;
    }

    public function processQueue(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->queue_table;
        
        // Get pending emails that are due to be sent
        $emails = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE status = 'pending' 
            AND scheduled_for <= %s 
            AND attempts < 3 
            ORDER BY scheduled_for ASC 
            LIMIT 50",
            current_time('mysql')
        ));

        foreach ($emails as $email) {
            $this->sendEmail($email);
        }

        // Clean up old successful emails
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name 
            WHERE status = 'sent' 
            AND created_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
    }

    private function sendEmail(\stdClass $email): void {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->queue_table;

        $headers = maybe_unserialize($email->headers);
        $attachments = maybe_unserialize($email->attachments);

        $success = wp_mail(
            $email->to_email,
            $email->subject,
            $email->message,
            $headers,
            $attachments
        );

        if ($success) {
            $wpdb->update(
                $table_name,
                [
                    'status' => 'sent',
                    'last_attempt' => current_time('mysql')
                ],
                ['id' => $email->id],
                ['%s', '%s'],
                ['%d']
            );
        } else {
            $wpdb->update(
                $table_name,
                [
                    'status' => 'failed',
                    'attempts' => $email->attempts + 1,
                    'last_attempt' => current_time('mysql'),
                    'error_message' => 'Failed to send email'
                ],
                ['id' => $email->id],
                ['%s', '%d', '%s', '%s'],
                ['%d']
            );

            do_action('wpwps_log_error', 'Email send failed', [
                'email_id' => $email->id,
                'to' => $email->to_email,
                'subject' => $email->subject
            ]);
        }
    }

    public function scheduleQueueProcessing(): void {
        if (!wp_next_scheduled('wpwps_process_email_queue')) {
            wp_schedule_event(time(), 'every_5_minutes', 'wpwps_process_email_queue');
        }
    }

    public function getQueueStats(): array {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->queue_table;

        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM $table_name
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");

        return [
            'total' => (int) $stats->total,
            'pending' => (int) $stats->pending,
            'sent' => (int) $stats->sent,
            'failed' => (int) $stats->failed
        ];
    }

    public function retryFailedEmails(): int {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->queue_table;

        return $wpdb->update(
            $table_name,
            [
                'status' => 'pending',
                'attempts' => 0,
                'error_message' => null
            ],
            [
                'status' => 'failed',
                'attempts' => ['<', 3]
            ],
            ['%s', '%d', null],
            ['%s', '%d']
        );
    }

    public function send(string $to, string $subject, string $message, array $headers = [], array $attachments = []): bool {
        $headers = $this->prepareHeaders($headers);
        $subject = $this->formatSubject($subject);
        $message = $this->formatMessage($message);

        try {
            $result = wp_mail($to, $subject, $message, $headers, $attachments);

            if ($result) {
                do_action('wpwps_log_info', 'Email sent successfully', [
                    'to' => $to,
                    'subject' => $subject
                ]);
            } else {
                throw new \Exception('wp_mail() returned false');
            }

            return $result;
        } catch (\Exception $e) {
            do_action('wpwps_log_error', 'Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendTemplate(string $to, string $template, array $data = [], array $headers = [], array $attachments = []): bool {
        $message = $this->renderTemplate($template, $data);
        $subject = $this->getTemplateSubject($template, $data);

        return $this->send($to, $subject, $message, $headers, $attachments);
    }

    public function queueTemplate(string $to, string $template, array $data = [], array $headers = [], array $attachments = []): bool {
        $message = $this->renderTemplate($template, $data);
        $subject = $this->getTemplateSubject($template, $data);

        return $this->queueEmail($to, $subject, $message, $headers, $attachments);
    }

    private function prepareHeaders(array $headers = []): array {
        $default_headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
        ];

        return array_merge($default_headers, $headers);
    }

    private function formatSubject(string $subject): string {
        $site_name = get_bloginfo('name');
        return sprintf('[%s] %s', $site_name, $subject);
    }

    private function formatMessage(string $message): string {
        ob_start();
        include(WPWPS_PLUGIN_DIR . 'templates/email/base.php');
        $template = ob_get_clean();

        return str_replace(
            ['{{content}}', '{{site_name}}', '{{site_url}}'],
            [$message, get_bloginfo('name'), get_bloginfo('url')],
            $template
        );
    }

    private function renderTemplate(string $template, array $data = []): string {
        $template_path = WPWPS_PLUGIN_DIR . 'templates/email/' . $template . '.php';

        if (!file_exists($template_path)) {
            throw new \Exception("Email template not found: $template");
        }

        extract($data);
        ob_start();
        include($template_path);
        return ob_get_clean();
    }

    private function getTemplateSubject(string $template, array $data = []): string {
        $subjects = [
            'order_confirmation' => __('Order Confirmation - #%s', 'wp-woocommerce-printify-sync'),
            'order_shipped' => __('Order Shipped - #%s', 'wp-woocommerce-printify-sync'),
            'order_cancelled' => __('Order Cancelled - #%s', 'wp-woocommerce-printify-sync'),
            'sync_error' => __('Sync Error Report', 'wp-woocommerce-printify-sync'),
            'low_stock' => __('Low Stock Alert - %s', 'wp-woocommerce-printify-sync')
        ];

        if (!isset($subjects[$template])) {
            throw new \Exception("Unknown email template: $template");
        }

        return sprintf($subjects[$template], $data['order_number'] ?? $data['product_name'] ?? '');
    }

    public function sendOrderConfirmation(\WC_Order $order): bool {
        $order_data = [
            'order_number' => $order->get_order_number(),
            'customer_name' => $order->get_formatted_billing_full_name(),
            'order_date' => $order->get_date_created()->date_i18n(get_option('date_format')),
            'order_status' => wc_get_order_status_name($order->get_status()),
            'order_items' => $this->formatOrderItems($order),
            'order_total' => $order->get_formatted_order_total(),
            'shipping_address' => $order->get_formatted_shipping_address(),
            'billing_address' => $order->get_formatted_billing_address(),
            'payment_method' => $order->get_payment_method_title()
        ];

        return $this->queueTemplate(
            $order->get_billing_email(),
            'order_confirmation',
            $order_data
        );
    }

    public function sendShippingNotification(\WC_Order $order, array $tracking): bool {
        $data = [
            'order_number' => $order->get_order_number(),
            'customer_name' => $order->get_formatted_billing_full_name(),
            'tracking_number' => $tracking['number'],
            'tracking_url' => $tracking['url'],
            'carrier' => $tracking['carrier'],
            'estimated_delivery' => $tracking['estimated_delivery'] ?? null
        ];

        return $this->queueTemplate(
            $order->get_billing_email(),
            'order_shipped',
            $data
        );
    }

    public function sendCancellationNotification(\WC_Order $order, string $reason = ''): bool {
        $data = [
            'order_number' => $order->get_order_number(),
            'customer_name' => $order->get_formatted_billing_full_name(),
            'cancellation_reason' => $reason,
            'refund_amount' => $order->get_total_refunded()
        ];

        return $this->queueTemplate(
            $order->get_billing_email(),
            'order_cancelled',
            $data
        );
    }

    public function sendSyncErrorReport(array $errors): bool {
        if (empty($errors)) {
            return true;
        }

        $admin_email = get_option('admin_email');
        return $this->queueTemplate($admin_email, 'sync_error', [
            'errors' => $errors,
            'date' => current_time('mysql')
        ]);
    }

    public function sendLowStockAlert(array $products): bool {
        if (empty($products)) {
            return true;
        }

        $admin_email = get_option('admin_email');
        return $this->queueTemplate($admin_email, 'low_stock', [
            'products' => $products,
            'date' => current_time('mysql')
        ]);
    }

    private function formatOrderItems(\WC_Order $order): string {
        $items_table = '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
        $items_table .= '<tr>';
        $items_table .= '<th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Product</th>';
        $items_table .= '<th style="text-align: center; padding: 8px; border: 1px solid #ddd;">Quantity</th>';
        $items_table .= '<th style="text-align: right; padding: 8px; border: 1px solid #ddd;">Price</th>';
        $items_table .= '</tr>';

        foreach ($order->get_items() as $item) {
            $items_table .= '<tr>';
            $items_table .= '<td style="text-align: left; padding: 8px; border: 1px solid #ddd;">' . $item->get_name() . '</td>';
            $items_table .= '<td style="text-align: center; padding: 8px; border: 1px solid #ddd;">' . $item->get_quantity() . '</td>';
            $items_table .= '<td style="text-align: right; padding: 8px; border: 1px solid #ddd;">' . wc_price($item->get_total()) . '</td>';
            $items_table .= '</tr>';
        }

        $items_table .= '</table>';
        return $items_table;
    }
}