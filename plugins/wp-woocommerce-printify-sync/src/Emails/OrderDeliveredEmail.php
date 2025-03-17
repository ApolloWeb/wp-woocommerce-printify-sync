<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Emails;

defined('ABSPATH') || exit;

class WC_Email_Printify_Delivered extends \WC_Email
{
    public function __construct()
    {
        $this->id = 'wpwps_order_delivered';
        $this->customer_email = true;
        $this->title = __('Printify Order Delivered', 'wp-woocommerce-printify-sync');
        $this->description = __('This email is sent when a Printify order is marked as delivered.', 'wp-woocommerce-printify-sync');
        $this->template_html = 'emails/printify-order-delivered.php';
        $this->template_plain = 'emails/plain/printify-order-delivered.php';
        $this->template_base = WPWPS_PLUGIN_PATH . 'templates/';

        parent::__construct();
    }

    public function trigger($order_id): void
    {
        $this->setup_locale();

        if ($order_id && ($order = wc_get_order($order_id))) {
            $this->object = $order;
            $this->recipient = $order->get_billing_email();

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

    public function get_default_subject(): string
    {
        return __('Your {site_title} order has been delivered!', 'wp-woocommerce-printify-sync');
    }

    public function get_default_heading(): string
    {
        return __('Order Delivered', 'wp-woocommerce-printify-sync');
    }
}

return new WC_Email_Printify_Delivered();