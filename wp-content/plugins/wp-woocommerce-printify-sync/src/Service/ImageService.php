<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Service;

use ApolloWeb\WPWooCommercePrintifySync\Storage\R2StorageProvider;

class ImageService
{
    private const IMAGE_META_KEY = '_wpwps_printify_image_url';
    private const FALLBACK_KEY = '_wpwps_printify_fallback_url';
    private const R2_PATH_KEY = '_wpwps_r2_path';
    private const CHUNK_SIZE = 10;

    private R2StorageProvider $storage;

    public function __construct(R2StorageProvider $storage)
    {
        $this->storage = $storage;
    }

    public function scheduleImageSync(array $productImages): void
    {
        // Split into chunks
        $chunks = array_chunk($productImages, self::CHUNK_SIZE);
        
        foreach ($chunks as $index => $chunk) {
            as_schedule_single_action(
                time() + ($index * 30), // 30 seconds between chunks
                'wpwps_process_image_chunk',
                [
                    'chunk' => $chunk,
                    'chunk_index' => $index,
                    'total_chunks' => count($chunks)
                ],
                'product-images'
            );
        }
    }

    public function processImageChunk(array $chunk, int $chunkIndex, int $totalChunks): void
    {
        foreach ($chunk as $imageData) {
            try {
                $result = $this->handleProductImage(
                    $imageData['product_id'],
                    $imageData['image_url']
                );

                // Update image meta
                if ($result['stored']) {
                    update_post_meta($imageData['product_id'], self::R2_PATH_KEY, $result['path']);
                    update_post_meta($imageData['product_id'], self::IMAGE_META_KEY, $result['url']);
                }

            } catch (\Exception $e) {
                // Schedule retry if needed
                $this->scheduleRetry($imageData, $e);
            }
        }

        // If this is the last chunk, trigger completion actions
        if ($chunkIndex === $totalChunks - 1) {
            do_action('wpwps_image_sync_completed');
        }
    }

    public function handleProductImage(int $productId, string $printifyImageUrl): array
    {
        // Store original Printify URL
        update_post_meta($productId, self::FALLBACK_KEY, $printifyImageUrl);

        // Generate R2 path
        $r2Path = $this->generateR2Path($productId, $printifyImageUrl);

        // Check if already in R2
        if ($this->storage->exists($r2Path)) {
            return [
                'url' => $this->storage->getPublicUrl($r2Path),
                'path' => $r2Path,
                'stored' => true
            ];
        }

        try {
            // Download image temporarily
            $tmpFile = download_url($printifyImageUrl);
            if (is_wp_error($tmpFile)) {
                throw new \Exception($tmpFile->get_error_message());
            }

            // Process with SMUSH if available
            if ($this->isSmushActive()) {
                $this->processWithSmush($tmpFile);
            }

            // Upload to R2
            $r2Url = $this->storage->upload($tmpFile, $r2Path);
            
            // Clean up
            @unlink($tmpFile);

            return [
                'url' => $r2Url,
                'path' => $r2Path,
                'stored' => true
            ];

        } catch (\Exception $e) {
            // Clean up on failure
            if (isset($tmpFile) && file_exists($tmpFile)) {
                @unlink($tmpFile);
            }

            // Return original URL as fallback
            return [
                'url' => $printifyImageUrl,
                'path' => '',
                'stored' => false
            ];
        }
    }

    private function scheduleRetry(array $imageData, \Exception $error): void
    {
        $retryCount = $imageData['retry_count'] ?? 0;
        
        if ($retryCount < 3) {
            as_schedule_single_action(
                time() + (300 * ($retryCount + 1)), // Exponential backoff
                'wpwps_retry_image_processing',
                [
                    'image_data' => array_merge($imageData, ['retry_count' => $retryCount + 1]),
                    'error' => $error->getMessage()
                ],
                'product-images'
            );
        } else {
            // Log permanent failure
            error_log(sprintf(
                'Failed to process image after %d retries: %s',
                $retryCount,
                $error->getMessage()
            ));
        }
    }

    private function generateR2Path(int $productId, string $url): string
    {
        $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        return sprintf(
            'products/%d/%s.%s',
            $productId,
            md5($url . microtime()),
            $ext
        );
    }

    private function isSmushActive(): bool
    {
        return class_exists('\WP_Smush') && class_exists('\Smush\Core\Core');
    }

    private function processWithSmush(string $filePath): void
    {
        if (!$this->isSmushActive()) {
            return;
        }

        try {
            global $WP_Smush;
            $smushCore = \Smush\Core\Core::get_instance();
            $smushCore->mod->smush->optimize($filePath);

            // Generate WebP if supported
            if (isset($WP_Smush->core()->mod->webp)) {
                $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filePath);
                $WP_Smush->core()->mod->webp->convert_to_webp($filePath, $webpPath);
                
                // If WebP was generated, upload it too
                if (file_exists($webpPath)) {
                    $webpR2Path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $this->currentR2Path);
                    $this->storage->upload($webpPath, $webpR2Path);
                    @unlink($webpPath);
                }
            }
        } catch (\Exception $e) {
            error_log("SMUSH processing failed: " . $e->getMessage());
        }
    }

    public function registerHooks(): void
    {
        // Register Action Scheduler hooks
        add_action('wpwps_process_image_chunk', [$this, 'processImageChunk'], 10, 3);
        add_action('wpwps_retry_image_processing', [$this, 'handleRetry']);
    }

    public function handleRetry(array $data): void
    {
        try {
            $result = $this->handleProductImage(
                $data['image_data']['product_id'],
                $data['image_data']['image_url']
            );

            if ($result['stored']) {
                update_post_meta($data['image_data']['product_id'], self::R2_PATH_KEY, $result['path']);
                update_post_meta($data['image_data']['product_id'], self::IMAGE_META_KEY, $result['url']);
            }
        } catch (\Exception $e) {
            $this->scheduleRetry($data['image_data'], $e);
        }
    }
}