<?php
/**
 * Email Queue Service
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;

/**
 * Class EmailQueueService
 *
 * Handles email queuing and processing
 */
class EmailQueueService
{
    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Container
     *
     * @var \ApolloWeb\WPWooCommercePrintifySync\Services\Container
     */
    private $container;

    /**
     * Constructor
     *
     * @param \ApolloWeb\WPWooCommercePrintifySync\Services\Container $container Service container.
     */
    public function __construct($container)
    {
        $this->container = $container;
        $this->logger = $container->get('logger');
    }

    /**
     * Initialize the service
     *
     * @return void
     */
    public function init(): void
    {
        // Register hooks for email queue processing
        add_action('wpwps_process_email_queue', [$this, 'processQueue']);
    }

    /**
     * Queue an email
     *
     * @param string       $to          Recipient email address
     * @param string       $subject     Email subject
     * @param string       $message     Email message
     * @param string|array $headers     Email headers
     * @param array        $attachments Email attachments
     * @return int|false The ID of the queued email or false on failure
     */
    public function queueEmail(
        string $to,
        string $subject,
        string $message,
        $headers = '',
        array $attachments = []
    ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_email_queue';
        
        // Validate email
        if (!is_email($to)) {
            $this->logger->error('Invalid email address', ['email' => $to]);
            return false;
        }
        
        // Format headers for database storage
        if (is_array($headers)) {
            $headers = implode("\n", $headers);
        }
        
        // Format attachments for database storage
        $serialized_attachments = empty($attachments) ? null : serialize($attachments);
        
        // Insert into queue
        $result = $wpdb->insert(
            $table_name,
            [
                'to_email' => $to,
                'subject' => $subject,
                'message' => $message,
                'headers' => $headers,
                'attachments' => $serialized_attachments,
                'status' => 'pending',
                'created_at' => current_time('mysql', true),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
        
        if (false === $result) {
            $this->logger->error('Failed to queue email', [
                'error' => $wpdb->last_error,
                'to' => $to,
                'subject' => $subject,
            ]);
            return false;
        }
        
        $email_id = $wpdb->insert_id;
        
        $this->logger->debug('Email queued', [
            'id' => $email_id,
            'to' => $to,
            'subject' => $subject,
        ]);
        
        return $email_id;
    }

    /**
     * Process the email queue
     *
     * @return void
     */
    public function processQueue(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_email_queue';
        $batch_size = intval(get_option('wpwps_email_queue_batch_size', 20));
        $max_attempts = 3;
        
        // Get pending emails
        $emails = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} 
                WHERE status = 'pending' AND attempts < %d 
                ORDER BY created_at ASC 
                LIMIT %d",
                $max_attempts,
                $batch_size
            )
        );
        
        if (empty($emails)) {
            $this->logger->debug('No pending emails to process');
            return;
        }
        
        $this->logger->debug('Processing email queue', ['count' => count($emails)]);
        
        foreach ($emails as $email) {
            // Mark as processing
            $wpdb->update(
                $table_name,
                ['status' => 'processing'],
                ['id' => $email->id],
                ['%s'],
                ['%d']
            );
            
            // Prepare attachments
            $attachments = empty($email->attachments) ? [] : unserialize($email->attachments);
            
            // Send the email
            $result = wp_mail(
                $email->to_email,
                $email->subject,
                $email->message,
                $email->headers,
                $attachments
            );
            
            // Update status based on result
            if ($result) {
                $wpdb->update(
                    $table_name,
                    [
                        'status' => 'sent',
                        'sent_at' => current_time('mysql', true),
                    ],
                    ['id' => $email->id],
                    ['%s', '%s'],
                    ['%d']
                );
                
                $this->logger->debug('Email sent successfully', [
                    'id' => $email->id,
                    'to' => $email->to_email,
                    'subject' => $email->subject,
                ]);
            } else {
                $attempts = intval($email->attempts) + 1;
                $status = $attempts >= $max_attempts ? 'failed' : 'pending';
                
                $wpdb->update(
                    $table_name,
                    [
                        'status' => $status,
                        'attempts' => $attempts,
                        'error_message' => 'Failed to send email',
                    ],
                    ['id' => $email->id],
                    ['%s', '%d', '%s'],
                    ['%d']
                );
                
                $this->logger->warning('Failed to send email', [
                    'id' => $email->id,
                    'to' => $email->to_email,
                    'subject' => $email->subject,
                    'attempts' => $attempts,
                    'status' => $status,
                ]);
            }
        }
    }

    /**
     * Clear old emails from the queue
     *
     * @param int $days Number of days to keep emails
     * @return int Number of emails deleted
     */
    public function clearOldEmails(int $days = 30): int
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_email_queue';
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s AND status IN ('sent', 'failed')",
                $date
            )
        );
        
        $this->logger->debug('Cleared old emails', [
            'count' => $result,
            'days' => $days,
        ]);
        
        return intval($result);
    }

    /**
     * Get queue statistics
     *
     * @return array
     */
    public function getQueueStats(): array
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_email_queue';
        
        $stats = [
            'pending' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0,
            'total' => 0,
        ];
        
        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$table_name} GROUP BY status"
        );
        
        if (!empty($results)) {
            foreach ($results as $row) {
                $stats[$row->status] = intval($row->count);
                $stats['total'] += intval($row->count);
            }
        }
        
        return $stats;
    }
}
