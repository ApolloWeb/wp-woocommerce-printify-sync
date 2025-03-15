<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImportService
{
    private const CRON_HOOK = 'wpwps_process_import_queue';
    private const BATCH_SIZE = 5;
    private string $currentTime;
    private string $currentUser;

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime; // 2025-03-15 18:08:03
        $this->currentUser = $currentUser; // ApolloWeb

        add_action(self::CRON_HOOK, [$this, 'processImportQueue']);
        add_filter('cron_schedules', [$this, 'addCronInterval']);
    }

    public function addCronInterval($schedules): array
    {
        $schedules['wpwps_one_minute'] = [
            'interval' => 60,
            'display' => __('Every Minute', 'wp-woocommerce-printify-sync')
        ];
        return $schedules;
    }

    public function scheduleImport(array $productIds): string
    {
        $jobId = uniqid('wpwps_import_', true);
        
        // Store the job metadata
        update_option("wpwps_import_{$jobId}_status", [
            'total' => count($productIds),
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'queue' => $productIds,
            'start_time' => $this->currentTime,
            'started_by' => $this->currentUser
        ]);

        // Schedule the first run immediately
        if (!wp_next_scheduled(self::CRON_HOOK, ['job_id' => $jobId])) {
            wp_schedule_event(time(), 'wpwps_one_minute', self::CRON_HOOK, ['job_id' => $jobId]);
        }

        return $jobId;
    }

    public function processImportQueue(string $jobId): void
    {
        $status = get_option("wpwps_import_{$jobId}_status");
        if (!$status || empty($status['queue'])) {
            $this->cleanupJob($jobId);
            return;
        }

        $batch = array_slice($status['queue'], 0, self::BATCH_SIZE);
        $remaining = array_slice($status['queue'], self::BATCH_SIZE);

        foreach ($batch as $printifyProductId) {
            try {
                $result = $this->importProduct($printifyProductId);
                $status['processed']++;
                if ($result) {
                    $status['success']++;
                } else {
                    $status['failed']++;
                }
            } catch (\Exception $e) {
                $status['failed']++;
                error_log("WPWPS Import Error: {$e->getMessage()}");
            }
        }

        $status['queue'] = $remaining;
        update_option("wpwps_import_{$jobId}_status", $status);

        if (empty($remaining)) {
            $this->cleanupJob($jobId);
        }
    }

    private function importProduct(string $printifyProductId): bool
    {
        global $wpdb;

        // Get product data from Printify
        $printifyApi = new PrintifyApiService();
        $productData = $printifyApi->getProduct($printifyProductId);

        if (!$productData) {
            throw new \Exception("Failed to fetch product data from Printify");
        }

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Create product post
            $productId = wp_insert_post([
                'post_title' => $productData['title'],
                'post_content' => $productData['description'],
                'post_status' => 'publish',
                'post_type' => 'product'
            ]);

            if (is_wp_error($productId)) {
                throw new \Exception($productId->get_error_message());
            }

            // Set product data
            wp_set_object_terms($productId, 'simple', 'product_type');

            // Set product meta
            update_post_meta($productId, '_price', $productData['retail_price']);
            update_post_meta($productId, '_regular_price', $productData['retail_price']);
            update_post_meta($productId, '_sku', $productData['sku']);
            update_post_meta($productId, '_stock_status', 'instock');
            update_post_meta($productId, '_manage_stock', 'no');
            update_post_meta($productId, '_printify_id', $printifyProductId);
            update_post_meta($productId, '_printify_sync_time', $this->currentTime);
            update_post_meta($productId, '_printify_synced_by', $this->currentUser);

            // Import images
            $this->importProductImages($productId, $productData['images']);

            // Import variations if any
            if (!empty($productData['variants'])) {
                $this->importProductVariations($productId, $productData['variants']);
            }

            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    private function importProductImages(int $productId, array $images): void
    {
        foreach ($images as $index => $imageUrl) {
            $attachmentId = $this->downloadAndAttachImage($imageUrl, $productId);
            if ($attachmentId && $index === 0) {
                set_post_thumbnail($productId, $attachmentId);
            }
        }
    }

    private function downloadAndAttachImage(string $imageUrl, int $productId): int
    {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmpFile = download_url($imageUrl);
        if (is_wp_error($tmpFile)) {
            return 0;
        }

        $fileArray = [
            'name' => basename($imageUrl),
            'tmp_name' => $tmpFile
        ];

        $attachmentId = media_handle_sideload($fileArray, $productId);
        if (is_wp_error($attachmentId)) {
            @unlink($tmpFile);
            return 0;
        }

        return $attachmentId;
    }

    private function importProductVariations(int $productId, array $variations): void
    {
        wp_set_object_terms($productId, 'variable', 'product_type');

        foreach ($variations as $variation) {
            $variationId = wp_insert_post([
                'post_title' => "Product #{$productId} Variation",
                'post_type' => 'product_variation',
                'post_parent' => $productId,
                'post_status' => 'publish'
            ]);

            if (!is_wp_error($variationId)) {
                update_post_meta($variationId, '_price', $variation['retail_price']);
                update_post_meta($variationId, '_regular_price', $variation['retail_price']);
                update_post_meta($variationId, '_sku', $variation['sku']);
                update_post_meta($variationId, '_printify_variant_id', $variation['id']);

                // Set attributes
                foreach ($variation['options'] as $name => $value) {
                    update_post_meta($variationId, "attribute_$name", $value);
                }
            }
        }
    }

    private function cleanupJob(string $jobId): void
    {
        wp_clear_scheduled_hook(self::CRON_HOOK, ['job_id' => $jobId]);
        
        // Keep the status for 24 hours for reference
        wp_schedule_single_event(
            time() + DAY_IN_SECONDS,
            'wpwps_cleanup_import_job',
            ['job_id' => $jobId]
        );
    }

    public function getImportProgress(string $jobId): array
    {
        $status = get_option("wpwps_import_{$jobId}_status", []);
        if (!$status) {
            return [
                'progress' => 0,
                'status' => 'error',
                'message' => __('Import job not found.', 'wp-woocommerce-printify-sync')
            ];
        }

        $progress = ($status['processed'] / $status['total']) * 100;

        return [
            'progress' => round($progress, 2),
            'processed' => $status['processed'],
            'total' => $status['total'],
            'success' => $status['success'],
            'failed' => $status['failed'],
            'status' => empty($status['queue']) ? 'complete' : 'processing',
            'start_time' => $status['start_time'],
            'started_by' => $status['started_by']
        ];
    }
}