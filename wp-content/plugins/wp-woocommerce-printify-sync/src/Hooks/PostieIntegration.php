<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Hooks;

use ApolloWeb\WPWooCommercePrintifySync\Services\AIEmailProcessor;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class PostieIntegration
{
    use TimeStampTrait;

    private AIEmailProcessor $emailProcessor;
    private LoggerInterface $logger;

    public function __construct(AIEmailProcessor $emailProcessor, LoggerInterface $logger)
    {
        $this->emailProcessor = $emailProcessor;
        $this->logger = $logger;
    }

    public function register(): void
    {
        add_filter('postie_post_before', [$this, 'processIncomingEmail'], 10, 2);
        add_action('postie_post_after', [$this, 'afterEmailProcessed'], 10, 2);
    }

    public function processIncomingEmail(array $post, array $email): array
    {
        try {
            // Process email with AI
            $processedData = $this->emailProcessor->processEmail($email);
            
            // Merge with existing post data
            return array_merge($post, $processedData);

        } catch (\Exception $e) {
            $this->logger->error('Failed to process email with AI', $this->addTimeStampData([
                'subject' => $email['subject'],
                'error' => $e->getMessage()
            ]));
            
            // Return original post data if processing fails
            return $post;
        }
    }

    public function afterEmailProcessed(int $post_id, array $email): void
    {
        try {
            // Link orders if found
            $orderIds = get_post_meta($post_id, '_wpwps_order_ids', true);
            if (!empty($orderIds)) {
                foreach ($orderIds as $orderId) {
                    $order = wc_get_order($orderId);
                    if ($order) {
                        $order->add_order_note(
                            sprintf(
                                __('Support ticket created: #%d', 'wp-woocommerce-printify-sync'),
                                $post_id
                            ),
                            false
                        );
                    }
                }
            }

            // Notify customer if needed
            $this->sendCustomerNotification($post_id, $email);

        } catch (\Exception $e) {
            $this->logger->error('Error in post-processing email', $this->addTimeStampData([
                'post_id' => $post_id,
                'error' => $e->getMessage()
            ]));
        }
    }

    private function sendCustomerNotification(int $post_id, array $email): void
    {
        // Implementation of customer notification system
    }
}