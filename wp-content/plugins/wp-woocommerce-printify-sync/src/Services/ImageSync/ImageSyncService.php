<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\ImageSync;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class ImageSyncService
{
    use TimeStampTrait;

    private StorageManager $storage;
    private LoggerInterface $logger;

    public function __construct(StorageManager $storage, LoggerInterface $logger)
    {
        $this->storage = $storage;
        $this->logger = $logger;
    }

    public function syncProductImages(int $productId, array $printifyData): void
    {
        try {
            $images = $printifyData['images'] ?? [];
            $mainImage = $images[0] ?? null;

            if ($mainImage) {
                $this->setProductImage($productId, $mainImage);
            }

            // Set gallery images
            $galleryImages = array_slice($images, 1);
            if (!empty($galleryImages)) {
                $this->setProductGallery($productId, $galleryImages);
            }

        } catch (\Exception $e) {
            $this->logger->error('Image sync failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }

    private function setProductImage(int $productId, array $imageData): void
    {
        $imageUrl = $imageData['src'];
        $imagePath = $this->downloadAndStoreImage($imageUrl, $productId);
        
        if ($imagePath) {
            $attachmentId = $this->createMediaAttachment($imagePath, $productId);
            set_post_thumbnail($productId, $attachmentId);
        }
    }

    private function setProductGallery(int $productId, array $images): void
    {
        $galleryIds = [];
        foreach ($images as $image) {
            $imagePath = $this->downloadAndStoreImage($image['src'], $productId);
            if ($imagePath) {
                $attachmentId = $this->createMediaAttachment($imagePath, $productId);
                $galleryIds[] = $attachmentId;
            }
        }

        update_post_meta($productId, '_product_image_gallery', implode(',', $galleryIds));
    }

    private function downloadAndStoreImage(string $url, int $productId): ?string
    {
        $filename = basename($url);
        $uniqueFilename = $productId . '-' . time() . '-' . $filename;
        
        try {
            $response = wp_remote_get($url);
            if (is_wp_error($response)) {
                throw new \Exception('Failed to download image: ' . $response->get_error_message());
            }

            $imageData = wp_remote_retrieve_body($response);
            $path = 'printify/products/' . $uniqueFilename;
            
            return $this->storage->store($path, $imageData);

        } catch (\Exception $e) {
            $this->logger->error('Image download failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
            return null;
        }
    }

    private function createMediaAttachment(string $path, int $productId): int
    {
        $filename = basename($path);
        $wp_filetype = wp_check_filetype($filename);

        $attachment = [
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attachId = wp_insert_attachment($attachment, $path, $productId);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $attach_data = wp_generate_attachment_metadata($attachId, $path);
        wp_update_attachment_metadata($attachId, $attach_data);

        return $attachId;
    }
}