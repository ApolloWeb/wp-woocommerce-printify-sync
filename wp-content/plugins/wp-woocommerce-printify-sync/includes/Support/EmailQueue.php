<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Support;

use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Core\Settings;

/**
 * Handles email queuing and processing via SMTP
 */
class EmailQueue {
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var Settings
     */
    private $settings;
    
    /**
     * @var EmailTemplateHandler
     */
    private $template_handler;
    
    /**
     * Constructor
     */
    public function __construct(Logger $logger, Settings $settings, EmailTemplateHandler $template_handler) {
        $this->logger = $logger;
        $this->settings = $settings;
        $this->template_handler = $template_handler;
    }
    
    /**
     * Initialize email queue
     */
    public function init(): void {
        // Create database table if needed
        $this->createTable();
        
        // Add dashboard widget
        add_action('wp_dashboard_setup', [$this, 'addDashboardWidget']);
        
        // AJAX endpoints
        add_action('wp_ajax_wpwps_process_email_queue_manually', [$this, 'processQueueManually']);
        add_action('wp_ajax_wpwps_get_queue_status', [$this, 'getQueueStatusAjax']);
        
        // Integration with WP Mail
        add_action('phpmailer_init', [$this, 'configureMailer']);
    }
    
    /**
     * Create email queue table
     */
    public function createTable(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_email_queue';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                to_email varchar(255) NOT NULL,
                subject text NOT NULL,
                message longtext NOT NULL,
                headers text,
                attachments text,
                status varchar(20) DEFAULT 'pending' NOT NULL,
                attempts smallint(5) DEFAULT 0 NOT NULL,
                error_message text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                scheduled_for datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                sent_at datetime,
                PRIMARY KEY  (id),
                KEY status (status),
                KEY created_at (created_at),
                KEY scheduled_for (scheduled_for)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            $this->logger->log('Email queue table created', 'info');
        }
    }
    
    /**
     * Add an email to the queue
     *
     * @param string|array $to Recipient email address(es)
     * @param string $subject Email subject
     * @param string $message Email message body
     * @param array|string $headers Optional. Email headers
     * @param array $attachments Optional. Files to attach
     * @param string|null $scheduled_time Optional. When to send the email (MySQL datetime format)
     * @return int|false The queue ID on success, false on failure
     */
    public function addToQueue($to, string $subject, string $message, $headers = '', array $attachments = [], string $scheduled_time = null) {
        global $wpdb;
        
        // Convert recipient to string if it's an array
        if (is_array($to)) {
            $to = implode(', ', $to);
        }
        
        // Convert headers to string if it's an array
        if (is_array($headers)) {
            $headers = implode("\n", $headers);
        }
        
        // Convert attachments to string (serialize)
        $attachments_serialized = maybe_serialize($attachments);
        
        // Set default scheduled time if not provided
        if (empty($scheduled_time)) {
            $scheduled_time = current_time('mysql');
        }
        
        // Insert into database
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpwps_email_queue',
            [
                'to_email' => $to,
                'subject' => $subject,
                'message' => $message,
                'headers' => $headers,
                'attachments' => $attachments_serialized,
                'status' => 'pending',
                'scheduled_for' => $scheduled_time
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            $this->logger->log('Failed to add email to queue: ' . $wpdb->last_error, 'error');
            return false;
        }
        
        $queue_id = $wpdb->insert_id;
        $this->logger->log("Added email to queue with ID {$queue_id}", 'debug');
        
        return $queue_id;
    }
    
    /**
     * Process the email queue (used by the scheduled action)
     *
     * @param int $limit Optional. Maximum number of emails to process
     * @return int Number of emails processed
     */
    public function processQueue(int $limit = 20): int {
        global $wpdb;
        
        // Get emails that are scheduled to be sent now
        $queue_items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpwps_email_queue 
                 WHERE status = 'pending' 
                 AND scheduled_for <= %s 
                 ORDER BY scheduled_for ASC 
                 LIMIT %d",
                current_time('mysql'),
                $limit
            ),
            ARRAY_A
        );
        
        if (empty($queue_items)) {
            return 0;
        }
        
        $processed = 0;
        
        foreach ($queue_items as $item) {
            // Update status to 'processing' to prevent duplicate processing
            $wpdb->update(
                $wpdb->prefix . 'wpwps_email_queue',
                ['status' => 'processing'],
                ['id' => $item['id']],
                ['%s'],
                ['%d']
            );
            
            try {
                // Prepare attachments
                $attachments = maybe_unserialize($item['attachments']);
                
                // Send email
                $result = $this->sendEmail(
                    $item['to_email'],
                    $item['subject'],
                    $item['message'],
                    $item['headers'],
                    $attachments
                );
                
                if ($result) {
                    // Update status to 'sent'
                    $wpdb->update(
                        $wpdb->prefix . 'wpwps_email_queue',
                        [
                            'status' => 'sent',
                            'sent_at' => current_time('mysql')
                        ],
                        ['id' => $item['id']],
                        ['%s', '%s'],
                        ['%d']
                    );
                    
                    $processed++;
                } else {
                    // Update attempt count and possibly status
                    $attempts = (int)$item['attempts'] + 1;
                    $max_attempts = 3; // Maximum number of attempts
                    
                    if ($attempts >= $max_attempts) {
                        $status = 'failed';
                    } else {
                        $status = 'pending';
                    }
                    
                    $wpdb->update(
                        $wpdb->prefix . 'wpwps_email_queue',
                        [
                            'status' => $status,
                            'attempts' => $attempts,
                            'error_message' => 'Failed to send email'
                        ],
                        ['id' => $item['id']],
                        ['%s', '%d', '%s'],
                        ['%d']
                    );
                }
            } catch (\Exception $e) {
                // Log error and update item
                $this->logger->log("Error processing email queue item {$item['id']}: " . $e->getMessage(), 'error');
                
                $wpdb->update(
                    $wpdb->prefix . 'wpwps_email_queue',
                    [
                        'status' => 'pending',
                        'attempts' => (int)$item['attempts'] + 1,
                        'error_message' => $e->getMessage()
                    ],
                    ['id' => $item['id']],
                    ['%s', '%d', '%s'],
                    ['%d']
                );
            }
        }
        
        $this->logger->log("Processed {$processed} emails from queue", 'info');
        
        return $processed;
    }
    
    /**
     * Process queue manually via AJAX
     */
    public function processQueueManually(): void {
        // Check permissions
        check_ajax_referer('wpwps_admin');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
            return;
        }
        
        // Process queue
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $processed = $this->processQueue($limit);
        
        // Get updated queue status
        $status = $this->getQueueStatus();
        
        wp_send_json_success([
            'message' => sprintf(__('Processed %d emails from queue', 'wp-woocommerce-printify-sync'), $processed),
            'processed' => $processed,
            'status' => $status
        ]);
    }
    
    /**
     * Get queue status via AJAX
     */
    public function getQueueStatusAjax(): void {
        // Check permissions
        check_ajax_referer('wpwps_admin');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
            return;
        }
        
        wp_send_json_success($this->getQueueStatus());
    }
    
    /**
     * Get current queue status
     *
     * @return array Queue status data
     */
    public function getQueueStatus(): array {
        global $wpdb;
        
        // Get counts by status
        $pending = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue WHERE status = 'pending'");
        $processing = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue WHERE status = 'processing'");
        $sent = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue WHERE status = 'sent' AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $failed = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue WHERE status = 'failed'");
        
        // Get emails ready to be sent
        $ready = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue 
             WHERE status = 'pending' 
             AND scheduled_for <= %s",
            current_time('mysql')
        ));
        
        // Get recent failures
        $recent_failures = $wpdb->get_results(
            "SELECT id, to_email, subject, error_message, updated_at 
             FROM {$wpdb->prefix}wpwps_email_queue 
             WHERE status = 'failed' 
             ORDER BY updated_at DESC 
             LIMIT 5",
            ARRAY_A
        );
        
        return [
            'pending' => (int) $pending,
            'processing' => (int) $processing,
            'sent' => (int) $sent,
            'failed' => (int) $failed,
            'ready' => (int) $ready,
            'recent_failures' => $recent_failures,
            'last_updated' => current_time('mysql')
        ];
    }
    
    /**
     * Configure PHPMailer to use SMTP
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance
     */
    public function configureMailer($phpmailer): void {
        // Check if SMTP is enabled
        $smtp_enabled = $this->settings->get('smtp_enabled', 'no');
        
        if ($smtp_enabled !== 'yes') {
            return;
        }
        
        // Get SMTP settings
        $host = $this->settings->get('smtp_host', '');
        $port = $this->settings->get('smtp_port', '25');
        $username = $this->settings->get('smtp_username', '');
        $password = $this->settings->get('smtp_password', '');
        $encryption = $this->settings->get('smtp_encryption', 'none');
        $from_email = $this->settings->get('smtp_from_email', get_option('admin_email'));
        $from_name = $this->settings->get('smtp_from_name', get_bloginfo('name'));
        
        // Configure PHPMailer
        $phpmailer->isSMTP();
        $phpmailer->Host = $host;
        $phpmailer->Port = $port;
        
        if (!empty($username) && !empty($password)) {
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $username;
            $phpmailer->Password = $password;
        }
        
        if ($encryption === 'ssl') {
            $phpmailer->SMTPSecure = 'ssl';
        } elseif ($encryption === 'tls') {
            $phpmailer->SMTPSecure = 'tls';
        }
        
        // Set from email and name
        $phpmailer->setFrom($from_email, $from_name);
    }
    
    /**
     * Send an email
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email message
     * @param string $headers Optional. Email headers
     * @param array $attachments Optional. Attachments
     * @return bool Whether the email was sent successfully
     */
    private function sendEmail(string $to, string $subject, string $message, string $headers, array $attachments = []): bool {
        // Check if using WooCommerce templates
        if ($this->settings->get('use_wc_templates', 'yes') === 'yes') {
            $message = $this->template_handler->getTemplate('default', [
                'heading' => $subject,
                'content' => $message
            ]);
        }
        
        // Use wp_mail to send the email
        $result = wp_mail($to, $subject, $message, $headers, $attachments);
        
        if (!$result) {
            $this->logger->log("Failed to send email to {$to}: " . (error_get_last()['message'] ?? 'Unknown error'), 'error');
        }
        
        return $result;
    }
    
    /**
     * Add dashboard widget for email queue status
     */
    public function addDashboardWidget(): void {
        // Only show to users who can manage options
        if (!current_user_can('manage_options')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'wpwps_email_queue_widget',
            __('Email Queue Status', 'wp-woocommerce-printify-sync'),
            [$this, 'renderDashboardWidget']
        );
    }
    
    /**
     * Render dashboard widget content
     */
    public function renderDashboardWidget(): void {
        $status = $this->getQueueStatus();
        ?>
        <div class="wpwps-email-queue-widget">
            <div class="wpwps-widget-stats">
                <div class="wpwps-stat">
                    <span class="wpwps-stat-label"><?php _e('Pending', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="wpwps-stat-value"><?php echo $status['pending']; ?></span>
                </div>
                <div class="wpwps-stat">
                    <span class="wpwps-stat-label"><?php _e('Ready to Send', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="wpwps-stat-value"><?php echo $status['ready']; ?></span>
                </div>
                <div class="wpwps-stat">
                    <span class="wpwps-stat-label"><?php _e('Sent (24h)', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="wpwps-stat-value"><?php echo $status['sent']; ?></span>
                </div>
                <div class="wpwps-stat">
                    <span class="wpwps-stat-label"><?php _e('Failed', 'wp-woocommerce-printify-sync'); ?></span>
                    <span class="wpwps-stat-value"><?php echo $status['failed']; ?></span>
                </div>
            </div>
            
            <?php if (!empty($status['recent_failures'])): ?>
            <h4><?php _e('Recent Failures', 'wp-woocommerce-printify-sync'); ?></h4>
            <ul class="wpwps-failures-list">
                <?php foreach ($status['recent_failures'] as $failure): ?>
                <li>
                    <strong><?php echo esc_html($failure['subject']); ?></strong><br>
                    <small><?php echo esc_html($failure['to_email']); ?> - <?php echo esc_html($failure['error_message']); ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            
            <div class="wpwps-widget-actions">
                <button type="button" class="button button-primary wpwps-process-queue">
                    <?php _e('Process Queue Now', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <p class="description">
                    <small><?php _e('Next automatic processing in:', 'wp-woocommerce-printify-sync'); ?> 
                    <span class="wpwps-next-cron"><?php 
                        $next_run = wp_next_scheduled('wpwps_process_email_queue');
                        echo human_time_diff(time(), $next_run);
                    ?></span></small>
                </p>
            </div>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.wpwps-process-queue').on('click', function() {
                    const $button = $(this);
                    $button.prop('disabled', true).text('<?php _e('Processing...', 'wp-woocommerce-printify-sync'); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wpwps_process_email_queue_manually',
                            _ajax_nonce: '<?php echo wp_create_nonce('wpwps_admin'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update stats
                                $('.wpwps-stat-value').each(function() {
                                    const $stat = $(this);
                                    const key = $stat.prev().text().toLowerCase().replace(/\s+/g, '_');
                                    if (response.data.status[key] !== undefined) {
                                        $stat.text(response.data.status[key]);
                                    }
                                });
                                
                                alert(response.data.message);
                            } else {
                                alert(response.data.message || '<?php _e('Error processing queue', 'wp-woocommerce-printify-sync'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php _e('Error processing queue', 'wp-woocommerce-printify-sync'); ?>');
                        },
                        complete: function() {
                            $button.prop('disabled', false).text('<?php _e('Process Queue Now', 'wp-woocommerce-printify-sync'); ?>');
                        }
                    });
                });
            });
        </script>
        <style>
            .wpwps-widget-stats {
                display: flex;
                justify-content: space-between;
                margin-bottom: 15px;
            }
            .wpwps-stat {
                text-align: center;
                flex: 1;
                padding: 10px 5px;
                border-right: 1px solid #eee;
            }
            .wpwps-stat:last-child {
                border-right: none;
            }
            .wpwps-stat-label {
                display: block;
                font-size: 11px;
                color: #777;
            }
            .wpwps-stat-value {
                display: block;
                font-size: 18px;
                font-weight: 600;
                color: #333;
            }
            .wpwps-failures-list {
                margin: 0;
                padding: 0;
                list-style: none;
                max-height: 100px;
                overflow-y: auto;
                border: 1px solid #eee;
                padding: 5px;
            }
            .wpwps-failures-list li {
                margin-bottom: 5px;
                padding-bottom: 5px;
                border-bottom: 1px solid #f5f5f5;
            }
            .wpwps-failures-list li:last-child {
                margin-bottom: 0;
                padding-bottom: 0;
                border-bottom: none;
            }
            .wpwps-widget-actions {
                margin-top: 15px;
                text-align: center;
            }
        </style>
        <?php
    }
}
