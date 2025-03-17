<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class PostieIntegrationService
{
    private EmailProcessingService $emailProcessor;
    private LoggerInterface $logger;

    public function __construct(
        EmailProcessingService $emailProcessor,
        LoggerInterface $logger
    ) {
        $this->emailProcessor = $emailProcessor;
        $this->logger = $logger;
    }

    public function register(): void
    {
        add_filter('postie_post_before', [$this, 'interceptEmail'], 10, 2);
        add_filter('postie_post_after', [$this, 'processEmailAttachments'], 10, 2);
    }

    public function interceptEmail($post_data, $email): array
    {
        try {
            // Process email and create/update ticket
            $ticketId = $this->emailProcessor->processEmail([
                'subject' => $email['subject'],
                'body' => $email['body'],
                'from' => $email['from'],
                'message_id' => $email['message_id'],
                'references' => $email['references'] ?? [],
                'date' => $email['date'],
                'attachments' => $email['attachments'] ?? []
            ]);

            // Prevent Postie from creating a regular post
            return ['skip' => true];

        } catch (\Exception $e) {
            $this->logger->error('Failed to process email via Postie', [
                'error' => $e->getMessage(),
                'email_id' => $email['message_id'] ?? null
            ]);
            
            // Let Postie handle it normally if our processing fails
            return $post_data;
        }
    }

    public function processEmailAttachments($post_id, $email): void
    {
        if (empty($email['attachments'])) {
            return;
        }

        foreach ($email['attachments'] as $attachment) {
            $this->handleAttachment($post_id, $attachment);
        }
    }

    private function handleAttachment(int $post_id, array $attachment): void
    {
        // Process attachment and link to ticket
        $file = [
            'name' => basename($attachment['filename']),
            'type' => $attachment['mime_type'],
            'tmp_name' => $attachment['filename'],
            'error' => 0,
            'size' => filesize($attachment['filename'])
        ];

        $upload = wp_handle_upload($file, ['test_form' => false]);

        if (!empty($upload['error'])) {
            $this->logger->error('Failed to upload attachment', [
                'error' => $upload['error'],
                'file' => $file['name']
            ]);
            return;
        }

        $attachment_id = wp_insert_attachment([
            'post_mime_type' => $upload['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', $file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        ], $upload['file'], $post_id);

        if (is_wp_error($attachment_id)) {
            $this->logger->error('Failed to create attachment', [
                'error' => $attachment_id->get_error_message(),
                'file' => $file['name']
            ]);
            return;
        }

        wp_update_attachment_metadata(
            $attachment_id,
            wp_generate_attachment_metadata($attachment_id, $upload['file'])
        );
    }
}