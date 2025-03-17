<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Models\ImageTracker;
use ApolloWeb\WPWooCommercePrintifySync\Services\Storage\StorageInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\ImageOptimization\ImageOptimizerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class ImageProcessor
{
    private StorageInterface $storage;
    private ImageOptimizerInterface $optimizer;
    private LoggerInterface $logger;
    private string $currentTime;
    private string $currentUser;

    public function __construct(
        StorageInterface $storage,
        ImageOptimizerInterface $optimizer,
        LoggerInterface $logger,
        string $currentTime,
        string $currentUser
    ) {
        $this->storage = $storage;
        $this->optimizer = $optimizer;
        $this->logger = $logger;
        $this->currentTime = $currentTime;
        $this->currentUser = $currentUser;
    }

    public function processProductImages(array $images, int $productId): array
    {
        $attachmentIds = [];

        foreach ($images as $image) {
            try {
                // Check if image has changed
                if (!ImageTracker::hasImageChanged($image['id'], $image['src'])) {
                    $this->logger->info('Image unchanged, skipping', [
                        'printify_image_id' => $image['id']
                    ]);
                    continue;
                }

                // Download and process image
                $tempFile = $this->downloadImage($image['src']);
                
                // Optimize image
                $optimizedPath = $this->optimizer->optimize($tempFile);
                
                // Generate WebP version
                $webpPath = $this->optimizer->generateWebP($optimizedPath);
                
                // Store images
                $storedPath = $this->storage->store($optimizedPath, basename($image['src']));
                $webpStoredPath = $webpPath ? $this->storage->store($webpPath, $this->getWebPFilename($image['src'])) : null;
                
                // Create attachment
                $attachmentId = $this->createAttachment($storedPath, $productId, basename($image['src']));
                
                // Track image
                ImageTracker::trackImage([
                    'product_id' => $productId,
                    'printify_image_id' => $image['id'],
                    'printify_image_url' => $image['src'],
                    'attachment_id' => $attachmentId,
                    'storage_path' => $storedPath,
                    'storage_provider' => $this->storage->getProviderName(),
                    'webp_path' => $webpStoredPath,
                    'sync_status' => 'completed',
                    'timestamp' => $this->currentTime,
                    'user' => $this->currentUser
                ]);

                $attachmentIds[] = $attachmentId;

                // Cleanup
                @unlink($tempFile);
                @unlink($optimizedPath);
                if ($webpPath) {
                    @unlink($webpPath);
                }

            } catch (\Exception $e) {
                $this->logger->error('Image processing failed', [
                    'image_id' => $image['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $attachmentIds;
    }

    private function getWebPFilename(string $originalPath): string
    {
        return pathinfo($originalPath, PATHINFO_FILENAME) . '.webp';
    }
}