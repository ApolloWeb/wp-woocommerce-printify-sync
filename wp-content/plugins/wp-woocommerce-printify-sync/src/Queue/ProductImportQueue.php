<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Queue;

class ProductImportQueue
{
    private string $currentTime = '2025-03-15 19:08:44';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        add_action('init', [$this, 'registerSchedules']);
        add_action('wpwps_product_import_cron', [$this, 'processQueue']);
    }

    public function registerSchedules(): void
    {
        if (!wp_next_scheduled('wpwps_product_import_cron')) {
            wp_schedule_event(time(), 'hourly', 'wpwps_product_import_cron');
        }
    }

    public function processQueue(): void
    {
        if (!get_option('wpwps_import_running', false)) {
            return;
        }

        $queue = get_option('wpwps_import_queue', []);
        $processed = 0;

        while (!empty($queue) && $processed < 10) {
            $item = array_shift($queue);
            
            try {
                do_action('wpwps_process_product_batch', $item);
                $processed++;
            } catch (\Exception $e) {
                error_log("Queue processing failed: " . $e->getMessage());
                // Put failed item back in queue
                array_unshift($queue, $item);
                break;
            }
        }

        update_option('wpwps_import_queue', $queue);
        
        if (empty($queue)) {
            update_option('wpwps_import_running', false);
        }
    }
}