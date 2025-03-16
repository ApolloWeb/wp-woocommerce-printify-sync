<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync;

class Plugin
{
    private WebhookManager $webhookManager;
    private LoggerInterface $logger;

    public function __construct(
        WebhookManager $webhookManager,
        LoggerInterface $logger
    ) {
        $this->webhookManager = $webhookManager;
        $this->logger = $logger;
    }

    public function activate(): void
    {
        try {
            // Setup webhooks programmatically
            $this->webhookManager->setupWebhooks();

            // Create required database tables
            $this->createTables();

            // Initialize default settings
            $this->initializeSettings();

            flush_rewrite_rules();

        } catch (\Exception $e) {
            $this->logger->error('Plugin activation failed', [
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }

    private function createTables(): void
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        // Webhook logs table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_webhook_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            webhook_id varchar(32) NOT NULL,
            topic varchar(64) NOT NULL,
            payload longtext NOT NULL,
            processed tinyint(1) DEFAULT 0,
            processed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY webhook_id (webhook_id),
            KEY topic (topic),
            KEY processed (processed)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function initializeSettings(): void
    {
        $defaultSettings = [
            'webhook_retry_attempts' => 3,
            'webhook_retry_delay' => 300, // 5 minutes
            'webhook_cleanup_days' => 30,
            'webhook_batch_size' => 100
        ];

        foreach ($defaultSettings as $key => $value) {
            if (get_option('wpwps_' . $key) === false) {
                update_option('wpwps_' . $key, $value);
            }
        }
    }
}