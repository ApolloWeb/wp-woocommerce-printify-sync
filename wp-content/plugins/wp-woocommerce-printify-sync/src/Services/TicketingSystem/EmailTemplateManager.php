<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\TicketingSystem;

use ApolloWeb\WPWooCommercePrintifySync\Foundation\AppContext;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LoggerAwareTrait;

class EmailTemplateManager
{
    use LoggerAwareTrait;

    private AppContext $context;
    private string $currentTime = '2025-03-15 20:24:53';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        $this->context = AppContext::getInstance();
        add_filter('woocommerce_email_classes', [$this, 'addEmailTemplates']);
    }

    public function addEmailTemplates(array $emails): array
    {
        // Add custom email templates
        $emails['wpwps_refund_request'] = new Emails\RefundRequestEmail();
        $emails['wpwps_reprint_request'] = new Emails\ReprintRequestEmail();
        $emails['wpwps_quality_issue'] = new Emails\QualityIssueEmail();
        $emails['wpwps_shipping_update'] = new Emails\ShippingUpdateEmail();
        $emails['wpwps_ticket_response'] = new Emails\TicketResponseEmail();
        
        return $emails;
    }

    public function getTemplateForAnalysis(array $analysis): string
    {
        $templateId = match($analysis['category']) {
            'REFUND_REQUEST' => 'refund_request',
            'REPRINT_REQUEST' => 'reprint_request',
            'QUALITY_ISSUE' => 'quality_issue',
            'SHIPPING_QUERY' => 'shipping_update',
            default => 'ticket_response'
        };

        return $this->getWooCommerceTemplate($templateId, $analysis);
    }

    private function getWooCommerceTemplate(string $templateId, array $analysis): string
    {
        // Get WooCommerce mailer
        $mailer = WC()->mailer();
        $template = $mailer->get_emails()["wpwps_{$templateId}"];

        if (!$template) {
            $this->log('warning', 'Template not found, using default', [
                'template_id' => $templateId
            ]);
            return $this->getDefaultTemplate($templateId);
        }

        // Prepare template variables
        $args = $this->prepareTemplateArgs($analysis);

        // Get template HTML
        return $template->get_content_html($args);
    }

    private function prepareTemplateArgs(array $analysis): array
    {
        $args = [
            'ticket_category' => $analysis['category'],
            'order_numbers' => $analysis['order_details']['order_numbers'],
            'customer_intent' => $analysis['customer_intent'],
            'evidence_provided' => $analysis['evidence_provided'],
            'timestamp' => $this->currentTime,
            'user' => $this->currentUser
        ];

        // Add order-specific data if available
        if (!empty($analysis['order_details']['order_numbers'])) {
            foreach ($analysis['order_details']['order_numbers'] as $orderNumber) {
                $order = wc_get_order($orderNumber);
                if ($order) {
                    $args['orders'][] = [
                        'id' => $order->get_id(),
                        'status' => $order->get_status(),
                        'total' => $order->get_total(),
                        'currency' => $order->get_currency(),
                        'items' => $this->getOrderItems($order)
                    ];
                }
            }
        }

        return $args;
    }

    private function getOrderItems($order): array
    {
        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $items[] = [
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => $item->get_total(),
                'sku' => $product ? $product->get_sku() : '',
                'printify_id' => $product ? get_post_meta($product->get_id(), '_printify_id', true) : ''
            ];
        }
        return $items;
    }
}