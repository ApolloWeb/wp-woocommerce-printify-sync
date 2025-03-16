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

        // General inquiries
        $this->addResponse(
            'general_inquiry',
            [
                'keywords' =>