<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Services;

class EmailTestingService {
    private $template_loader;
    private $smtp_service;
    private $logger;

    public function init() {
        add_action('wp_ajax_wpwps_test_email', [$this, 'handleTestEmail']);
        add_action('wp_ajax_wpwps_validate_template', [$this, 'validateTemplate']);
    }

    public function handleTestEmail() {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        $scenario = $_POST['scenario'] ?? 'new_order';
        $test_data = $this->getTestScenarios()[$scenario]['data'];

        try {
            $content = $this->template_loader->render(
                'emails/test-scenarios/' . $scenario . '.php', 
                $test_data
            );

            $result = $this->smtp_service->queueEmail(
                sanitize_email($_POST['test_email']),
                $this->getTestSubject($scenario),
                $content,
                ['test' => true]
            );

            wp_send_json_success([
                'message' => __('Test email queued successfully', 'wp-woocommerce-printify-sync'),
                'queue_id' => $result
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function getTestScenarios() {
        return [
            'new_order' => [
                'label' => __('New Order', 'wp-woocommerce-printify-sync'),
                'data' => $this->getOrderTestData()
            ],
            'support_ticket' => [
                'label' => __('Support Ticket', 'wp-woocommerce-printify-sync'),
                'data' => $this->getTicketTestData()
            ],
            'order_status' => [
                'label' => __('Order Status Update', 'wp-woocommerce-printify-sync'),
                'data' => $this->getStatusUpdateData()
            ]
        ];
    }

    private function generateTestContent() {
        return $this->template_loader->render('emails/test-email.php', [
            'test_data' => $this->getTestData()
        ]);
    }

    private function getTestData() {
        return [
            'customer_name' => 'John Doe',
            'order_number' => '#TEST-123',
            'ticket_id' => 'TIC-TEST-456',
            'timestamp' => current_time('mysql')
        ];
    }
}
