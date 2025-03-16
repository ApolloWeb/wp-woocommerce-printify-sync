<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

class AutomatedResponse
{
    private $conditions = [];
    private $responses = [];
    private $settings;

    public function __construct()
    {
        $this->settings = get_option('wpwps_automated_responses', []);
        $this->initializeDefaultResponses();
        add_action('wpwps_ticket_created', [$this, 'processNewTicket'], 10, 2);
        add_action('wpwps_printify_order_status_changed', [$this, 'processOrderStatusChange'], 10, 3);
    }

    private function initializeDefaultResponses(): void
    {
        // Order status related responses
        $this->addResponse(
            'order_shipping',
            [
                'keywords' => ['shipping', 'tracking', 'delivery', 'where is my order'],
                'conditions' => ['has_order' => true]
            ],
            [
                'subject' => __('Shipping Information for Order #{order_id}', 'wp-woocommerce-printify-sync'),
                'message' => $this->getDefaultShippingResponse()
            ]
        );

        // Product related responses
        $this->addResponse(
            'product_customization',
            [
                'keywords' => ['customize', 'personalize', 'custom design', 'modify'],
                'conditions' => ['has_product' => true]
            ],
            [
                'subject' => __('Product Customization Information', 'wp-woocommerce-printify-sync'),
                'message' => $this->getDefaultCustomizationResponse()
            ]
        );

        // Printify sync issues
        $this->addResponse(
            'sync_issue',
            [
                'keywords' => ['sync', 'not syncing', 'update failed', 'sync error'],
                'conditions' => ['has_printify_error' => true]
            ],
            [
                'subject' => __('Printify Sync Status Update', 'wp-woocommerce-printify-sync'),
                'message' => $this->getDefaultSyncResponse()
            ]
        );

        // General inquiries
        $this->addResponse(
            'general_inquiry',
            [
                'keywords' => ['question', 'help', 'support', 'information'],
                'conditions' => []
            ],
            [
                'subject' => __('Thank You for Contacting Us', 'wp-woocommerce-printify-sync'),
                'message' => $this->getDefaultGeneralResponse()
            ]
        );
    }

    public function processNewTicket(int $ticket_id, array $ticket_data): void
    {
        if (!$this->isAutomatedResponseEnabled()) {
            return;
        }

        $message = $ticket_data['message'] ?? '';
        $response = $this->findMatchingResponse($message, $ticket_data);

        if ($response) {
            $this->sendAutomatedResponse($ticket_id, $response, $ticket_data);
        }
    }

    public function processOrderStatusChange(int $order_id, string $old_status, string $new_status): void
    {
        if (!$this->isAutomatedResponseEnabled()) {
            return;
        }

        $ticket_id = $this->findRelatedTicket($order_id);
        if (!$ticket_id) {
            return;
        }

        $response = $this->getOrderStatusResponse($new_status, $order_id);
        if ($response) {
            $this->sendAutomatedResponse($ticket_id, $response, ['order_id' => $order_id]);
        }
    }

    private function findMatchingResponse(string $message, array $context): ?array
    {
        foreach ($this->responses as $id => $response) {
            if ($this->matchesConditions($response['conditions'], $context) && 
                $this->containsKeywords($message, $response['keywords'])) {
                return $this->prepareResponse($response['template'], $context);
            }
        }
        return null;
    }

    private function matchesConditions(array $conditions, array $context): bool
    {
        foreach ($conditions as $condition => $value) {
            switch ($condition) {
                case 'has_order':
                    if ($value && empty($context['order_id'])) {
                        return false;
                    }
                    break;
                case 'has_product':
                    if ($value && empty($context['product_id'])) {
                        return false;
                    }
                    break;
                case 'has_printify_error':
                    if ($value && empty($context['printify_error'])) {
                        return false;
                    }
                    break;
            }
        }
        return true;
    }

    private function containsKeywords(string $message, array $keywords): bool
    {
        $message = strtolower($message);
        foreach ($keywords as $keyword) {
            if (strpos($message, strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }

    private function prepareResponse(array $template, array $context): array
    {
        $subject = $template['subject'];
        $message = $template['message'];

        // Replace placeholders
        $placeholders = [
            '{order_id}' => $context['order_id'] ?? '',
            '{product_id}' => $context['product_id'] ?? '',
            '{customer_name}' => $context['customer_name'] ?? '',
            '{site_title}' => get_bloginfo('name'),
        ];

        return [
            'subject' => strtr($subject, $placeholders),
            'message' => strtr($message, $placeholders),
        ];
    }

    private function sendAutomatedResponse(int $ticket_id, array $response, array $context): void
    {
        $note_data = [
            'ticket_id' => $ticket_id,
            'content' => $response['message'],
            'is_automated' => true,
            'context' => $context,
        ];

        // Add the automated response as a note
        do_action('wpwps_add_ticket_note', $note_data);

        // Send the email
        $mailer = WC()->mailer();
        $email = $mailer->get_emails()['wpwps_ticket_reply'];
        if ($email) {
            $email->trigger($ticket_id, $note_data);
        }
    }

    private function isAutomatedResponseEnabled(): bool
    {
        return get_option('wpwps_enable_auto_response', 'yes') === 'yes';
    }

    private function getDefaultShippingResponse(): string
    {
        return __(
            "Thank you for inquiring about your order's shipping status.\n\n" .
            "Your order #{order_id} is being processed through our print-on-demand service, Printify. " .
            "Once your item ships, you'll receive a tracking number automatically.\n\n" .
            "Standard processing time is 2-7 business days plus shipping time. " .
            "We'll keep you updated on any changes to your order status.",
            'wp-woocommerce-printify-sync'
        );
    }

    private function getDefaultCustomizationResponse(): string
    {
        return __(
            "Thank you for your interest in customizing your product.\n\n" .
            "For product #{product_id}, customization options are managed through our print-on-demand partner, Printify. " .
            "We'll review your request and get back to you with specific customization possibilities.\n\n" .
            "Please note that some customization options may be limited by the printing process and product type.",
            'wp-woocommerce-printify-sync'
        );
    }

    private function getDefaultSyncResponse(): string
    {
        return __(
            "We've received your report about a sync issue.\n\n" .
            "Our system has detected the sync error and our technical team has been notified. " .
            "We're working to resolve this as quickly as possible.\n\n" .
            "We'll update you as soon as the sync is restored. In the meantime, your order is safely stored in our system.",
            'wp-woocommerce-printify-sync'
        );
    }

    private function getDefaultGeneralResponse(): string
    {
        return __(
            "Thank you for contacting us.\n\n" .
            "We've received your message and will get back to you within 24-48 hours. " .
            "If your inquiry is urgent or related to a specific order, please include your order number in any future correspondence.\n\n" .
            "We appreciate your patience and look forward to assisting you.",
            'wp-woocommerce-printify-sync'
        );
    }
}