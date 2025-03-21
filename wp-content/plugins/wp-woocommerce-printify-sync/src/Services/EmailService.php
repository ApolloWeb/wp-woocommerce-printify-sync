<?php
/**
 * Email Service for handling emails.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Email service for handling email queue and sending.
 */
class EmailService
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Initialize the service.
     *
     * @return void
     */
    public function init()
    {
        // Register hooks
        add_action('wpwps_process_email_queue', [$this, 'processEmailQueue']);
    }

    /**
     * Queue an email to be sent.
     *
     * @param string|array $to          Email recipient(s).
     * @param string       $subject     Email subject.
     * @param string       $message     Email message.
     * @param string|array $headers     Email headers.
     * @param array        $attachments Email attachments.
     * @param int          $scheduled_time Timestamp when to send the email. Default is now.
     * @return int|false   Email queue ID on success, false on failure.
     */
    public function queueEmail($to, $subject, $message, $headers = '', $attachments = [], $scheduled_time = 0)
    {
        global $wpdb;

        // Validate inputs
        if (empty($to) || empty($subject) || empty($message)) {
            $this->logger->error('Invalid email parameters: recipient, subject, or message is empty');
            return false;
        }

        // Set scheduled time to now if not provided
        if (empty($scheduled_time)) {
            $scheduled_time = time();
        }

        // Convert to array if string
        if (is_string($to)) {
            $to = [$to];
        }

        // Serialize arrays
        $to_string = implode(',', $to);
        $headers_string = is_array($headers) ? implode("\n", $headers) : $headers;
        $attachments_string = is_array($attachments) ? serialize($attachments) : '';

        // Insert into email queue
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpwps_email_queue',
            [
                'to_email' => $to_string,
                'subject' => $subject,
                'message' => $message,
                'headers' => $headers_string,
                'attachments' => $attachments_string,
                'status' => 'pending',
                'scheduled_time' => date('Y-m-d H:i:s', $scheduled_time),
                'created_at' => current_time('mysql'),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            $this->logger->error('Failed to queue email: ' . $wpdb->last_error);
            return false;
        }

        $queue_id = $wpdb->insert_id;
        $this->logger->info("Email queued with ID {$queue_id}: {$subject}");

        return $queue_id;
    }

    /**
     * Process the email queue.
     *
     * @param int $limit Maximum number of emails to process. Default is 20.
     * @return int Number of emails processed.
     */
    public function processEmailQueue($limit = 20)
    {
        global $wpdb;

        $this->logger->info("Processing email queue (limit: {$limit})");

        // Get pending emails
        $emails = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpwps_email_queue 
                WHERE status = 'pending' 
                AND scheduled_time <= %s 
                ORDER BY scheduled_time ASC 
                LIMIT %d",
                current_time('mysql'),
                $limit
            )
        );

        if (empty($emails)) {
            $this->logger->info('No pending emails found in the queue');
            return 0;
        }

        $processed_count = 0;

        foreach ($emails as $email) {
            // Set the email as processing
            $wpdb->update(
                $wpdb->prefix . 'wpwps_email_queue',
                ['status' => 'processing'],
                ['id' => $email->id],
                ['%s'],
                ['%d']
            );

            // Process the email
            $success = $this->sendEmail(
                explode(',', $email->to_email),
                $email->subject,
                $email->message,
                $email->headers,
                !empty($email->attachments) ? unserialize($email->attachments) : []
            );

            // Update the email status
            if ($success) {
                $wpdb->update(
                    $wpdb->prefix . 'wpwps_email_queue',
                    [
                        'status' => 'sent',
                        'sent_time' => current_time('mysql'),
                    ],
                    ['id' => $email->id],
                    ['%s', '%s'],
                    ['%d']
                );

                $this->logger->info("Email sent from queue (ID: {$email->id}): {$email->subject}");
                $processed_count++;
            } else {
                // Increment retry count
                $retry_count = (int) $email->retry_count + 1;
                $max_retries = 3;

                if ($retry_count >= $max_retries) {
                    $status = 'failed';
                    $this->logger->error("Email sending failed after {$max_retries} attempts (ID: {$email->id}): {$email->subject}");
                } else {
                    $status = 'pending';
                    $this->logger->warning("Email sending failed, will retry later (ID: {$email->id}, Attempt: {$retry_count}/{$max_retries}): {$email->subject}");
                }

                $wpdb->update(
                    $wpdb->prefix . 'wpwps_email_queue',
                    [
                        'status' => $status,
                        'retry_count' => $retry_count,
                        'error_message' => 'Email sending failed',
                    ],
                    ['id' => $email->id],
                    ['%s', '%d', '%s'],
                    ['%d']
                );
            }
        }

        $this->logger->info("Processed {$processed_count} emails from the queue");
        return $processed_count;
    }

    /**
     * Send an email using wp_mail.
     *
     * @param string|array $to          Email recipient(s).
     * @param string       $subject     Email subject.
     * @param string       $message     Email message.
     * @param string|array $headers     Email headers.
     * @param array        $attachments Email attachments.
     * @return bool Whether the email was sent successfully.
     */
    private function sendEmail($to, $subject, $message, $headers = '', $attachments = [])
    {
        // Add default headers if not provided
        if (empty($headers)) {
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            ];
        }

        // Send the email
        $result = wp_mail($to, $subject, $message, $headers, $attachments);

        if (!$result) {
            $this->logger->error('Failed to send email: ' . $subject);
        }

        return $result;
    }

    /**
     * Get the count of emails in the queue by status.
     *
     * @param string $status Email status. Default is 'pending'.
     * @return int Email count.
     */
    public function getEmailQueueCount($status = 'pending')
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue WHERE status = %s",
                $status
            )
        );
    }

    /**
     * Get emails from the queue.
     *
     * @param string $status Email status. Default is 'pending'.
     * @param int    $limit  Maximum number of emails to return. Default is 10.
     * @return array Emails from the queue.
     */
    public function getEmailQueue($status = 'pending', $limit = 10)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpwps_email_queue 
                WHERE status = %s 
                ORDER BY scheduled_time ASC 
                LIMIT %d",
                $status,
                $limit
            )
        );
    }

    /**
     * Clear failed emails from the queue.
     *
     * @return int Number of emails cleared.
     */
    public function clearFailedEmails()
    {
        global $wpdb;

        $count = $wpdb->query(
            "DELETE FROM {$wpdb->prefix}wpwps_email_queue WHERE status = 'failed'"
        );

        $this->logger->info("Cleared {$count} failed emails from the queue");
        return $count;
    }
}
