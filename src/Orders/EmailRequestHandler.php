<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

class EmailRequestHandler {
    private $smtp_service;
    private $template_loader;
    private $settings;

    const PRINTIFY_SUPPORT_EMAIL = 'support@printify.com';

    public function __construct(SMTPService $smtp_service, TemplateLoader $template_loader) {
        $this->smtp_service = $smtp_service;
        $this->template_loader = $template_loader;
        $this->settings = get_option('wpwps_email_settings', []);
    }

    public function sendReprintRequest($order_id, $reason, $images = []) {
        $order = wc_get_order($order_id);
        $printify_order_id = $order->get_meta('_printify_order_id');

        $subject = sprintf('Reprint Request - Order #%s (Printify #%s)', 
            $order->get_order_number(),
            $printify_order_id
        );

        $template_data = [
            'order' => $order,
            'printify_order_id' => $printify_order_id,
            'reason' => $reason,
            'items' => $this->getOrderItems($order)
        ];

        return $this->smtp_service->queueEmail(
            self::PRINTIFY_SUPPORT_EMAIL,
            $subject,
            $this->template_loader->render('emails/reprint-request.php', $template_data),
            $this->prepareAttachments($images)
        );
    }

    public function sendRefundRequest($order_id, $reason) {
        $order = wc_get_order($order_id);
        $printify_order_id = $order->get_meta('_printify_order_id');

        $subject = sprintf('Refund Request - Order #%s (Printify #%s)', 
            $order->get_order_number(),
            $printify_order_id
        );

        $template_data = [
            'order' => $order,
            'printify_order_id' => $printify_order_id,
            'reason' => $reason,
            'refund_amount' => $order->get_total()
        ];

        return $this->smtp_service->queueEmail(
            self::PRINTIFY_SUPPORT_EMAIL,
            $subject,
            $this->template_loader->render('emails/refund-request.php', $template_data)
        );
    }

    private function prepareAttachments($images) {
        $attachments = [];
        foreach ($images as $image) {
            if (is_array($image) && isset($image['tmp_name'])) {
                $attachments[] = [
                    'path' => $image['tmp_name'],
                    'name' => $image['name'],
                    'type' => $image['type']
                ];
            }
        }
        return $attachments;
    }
}
