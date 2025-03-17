<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Email_Printify_Shipped extends WC_Email
{
    public function __construct()
    {
        $this->id = 'wpwps_order_shipped';
        $this->title = __('Printify Order Shipped', 'wp-woocommerce-printify-sync');
        $this->description = __('Order shipped notification emails are sent when an order is marked as shipped by Printify.', 'wp-woocommerce-printify-sync');
        $this->template_html = 'emails/printify-order-shipped.php';
        $this->template_plain = 'emails/plain/printify-order-shipped.php';
        $this->template_base = WPWPS_PLUGIN_PATH . 'templates/';
        $this->placeholders = [
            '{order_number}' => '',
            '{tracking_number}' => '',
            '{carrier}' => '',
        ];

        // Call parent constructor
        parent::__construct();
    }

    public function get_default_subject(): string
    {
        return __('Your {site_title} order #{order_number} has been shipped', 'wp-woocommerce-printify-sync');
    }

    public function get_default_heading(): string
    {
        return __('Your order has been shipped', 'wp-woocommerce-printify-sync');
    }

    public function trigger($order_id): void
    {
        $this->setup_locale();

        if ($order_id && ($order = wc_get_order($order_id))) {
            $this->object = $order;
            $this->recipient = $order->get_billing_email();
            
            $this->placeholders['{order_number}'] = $order->get_order_number();
            $this->placeholders['{tracking_number}'] = $order->get_meta('_printify_tracking_number');
            $this->placeholders['{carrier}'] = $order->get_meta('_printify_tracking_carrier');

            if ($this->is_enabled() && $this->recipient) {
                $this->send(
                    $this->get_recipient(),
                    $this->get_subject(),
                    $this->get_content(),
                    $this->get_headers(),
                    $this->get_attachments()
                );
            }
        }

        $this->restore_locale();
    }

    public function get_content_html(): string
    {
        return wc_get_template_html(
            $this->template_html,
            [
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this,
            ],
            '',
            $this->template_base
        );
    }

    public function get_content_plain(): string
    {
        return wc_get_template_html(
            $this->template_plain,
            [
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this,
            ],
            '',
            $this->template_base
        );
    }
}

return new WC_Email_Printify_Shipped();