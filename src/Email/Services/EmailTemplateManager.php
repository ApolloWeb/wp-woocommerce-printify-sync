<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Services;

class EmailTemplateManager {
    private $template_loader;
    
    public function init() {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_preview_email', [$this, 'previewEmail']);
        add_action('wp_ajax_wpwps_save_template', [$this, 'saveTemplate']);
    }

    public function getAvailableVariables() {
        return [
            '{customer_name}' => __('Customer Name', 'wp-woocommerce-printify-sync'),
            '{order_number}' => __('Order Number', 'wp-woocommerce-printify-sync'),
            '{ticket_id}' => __('Ticket ID', 'wp-woocommerce-printify-sync'),
            '{company_name}' => __('Company Name', 'wp-woocommerce-printify-sync')
        ];
    }

    public function previewEmail() {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');
        
        $template = $this->renderTemplate($_POST['template'], [
            'customer_name' => 'John Doe',
            'order_number' => '#12345',
            'ticket_id' => 'TIC-789'
        ]);

        wp_send_json_success(['html' => $template]);
    }
}
