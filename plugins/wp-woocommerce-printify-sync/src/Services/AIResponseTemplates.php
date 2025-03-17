<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AIResponseTemplates
{
    private const CURRENT_TIME = '2025-03-15 22:17:37';
    private ConfigService $config;
    private LoggerInterface $logger;

    private array $templates = [
        'refund' => [
            'positive' => [
                'greeting' => 'I understand you\'d like to request a refund for your order. I\'m here to help make this process as smooth as possible.',
                'body' => 'I\'ve reviewed your order details and initiated the refund process. You can expect the refund to be processed within 3-5 business days.',
                'closing' => 'Please let me know if you have any questions about the refund process.'
            ],
            'negative' => [
                'greeting' => 'I sincerely apologize for any inconvenience with your order.',
                'body' => 'I understand your frustration and I want to assure you that I\'ll personally handle your refund request right away.',
                'closing' => 'I\'ll make sure this is resolved as quickly as possible for you.'
            ]
        ],
        'order_inquiry' => [
            'positive' => [
                'greeting' => 'Thank you for inquiring about your order. I\'d be happy to help you with that.',
                'body' => 'Let me provide you with the latest update on your order.',
                'closing' => 'Please don\'t hesitate to reach out if you need any additional information.'
            ],
            'negative' => [
                'greeting' => 'I understand you\'re concerned about your order, and I\'m here to help.',
                'body' => 'I\'ve looked into your order status and I want to provide you with a detailed update.',
                'closing' => 'I\'ll personally monitor your order and keep you updated on any changes.'
            ]
        ],
        'product_inquiry' => [
            'positive' => [
                'greeting' => 'Thank you for your interest in our products.',
                'body' => 'I\'d be happy to provide you with detailed information about the product you\'re interested in.',
                'closing' => 'Let me know if you have any other questions about our products.'
            ],
            'negative' => [
                'greeting' => 'I understand you have some concerns about our product.',
                'body' => 'I want to address your concerns directly and provide you with accurate information.',
                'closing' => 'I\'m here to ensure you have all the information you need to make an informed decision.'
            ]
        ]
    ];

    public function __construct(ConfigService $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getTemplate(string $category, string $sentiment, array $context = []): string
    {
        $template = $this->templates[$category][$sentiment] ?? $this->templates['general'][$sentiment];
        
        return $this->customizeTemplate($template, $context);
    }

    private function customizeTemplate(array $template, array $context): string
    {
        $response = $template['greeting'] . "\n\n";

        // Add order-specific information
        if (!empty($context['order_info'])) {
            $response .= $this->getOrderSpecificContent($context['order_info']);
        }

        $response .= $template['body'] . "\n\n";

        // Add tracking information if available
        if (!empty($context['tracking_info'])) {
            $response .= $this->getTrackingContent($context['tracking_info']);
        }

        $response .= $template['closing'];

        return $response;
    }

    private function getOrderSpecificContent(array $orderInfo): string
    {
        $content = "";
        
        if (!empty($orderInfo['status'])) {
            $content .= sprintf(
                "Your order #%s is currently %s. ",
                $orderInfo['order_number'],
                $this->formatOrderStatus($orderInfo['status'])
            );
        }

        if (!empty($orderInfo['estimated_delivery'])) {
            $content .= sprintf(
                "The estimated delivery date is %s. ",
                $orderInfo['estimated_delivery']
            );
        }

        return $content;
    }

    private function getTrackingContent(array $trackingInfo): string
    {
        return sprintf(
            "Your order is being shipped via %s. You can track your package using this tracking number: %s\n",
            $trackingInfo['carrier'],
            $trackingInfo['number']
        );
    }

    private function formatOrderStatus(string $status): string
    {
        $statuses = [
            'processing' => 'being processed',
            'completed' => 'completed',
            'on-hold' => 'on hold',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'failed',
            'pending' => 'pending'
        ];

        return $statuses[$status] ?? $status;
    }
}