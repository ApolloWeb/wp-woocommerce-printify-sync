<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Products;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ImageSync
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function syncProductImages(int $productId, array $images): void
    {
        $existingAttachments = $this->getExistingAttachments($productId);
        $newAttachmentIds = [];

        foreach ($images as $index => $imageUrl) {
            try {
                $attachmentId = $this->uploadImage($imageUrl, $productId);
                if ($attachmentId) {
                    $newAttachmentIds[] = $attachmentId;
                    if ($index === 0) {
                        set_post_thumbnail($productId, $attachmentId);
                    }
                }
            } catch (\Exception $e) {
                error_log("Image Sync Error: " . $e->getMessage());
            }
        }

        // Remove old images that are no longer used
        foreach ($existingAttachments as $oldAttachment) {
            if (!in_array($oldAttachment->ID, $newAttachmentIds)) {
                wp_delete_attachment($oldAttachment->ID, true);
            }
        }

        // Update product gallery
        if (count($newAttachmentIds) > 1) {
            update_post_meta($productId, '_product_image_gallery', implode(',', array_slice($newAttachmentIds, 1)));
        }
    }

    private function uploadImage(string $url, int $productId): ?int
    {
        try {
            $response = $this->client->get($url);
            $contentType = $response->getHeader('Content-Type')[0] ?? '';
            if (!str_starts_with($contentType, 'image/')) {
                return null;
            }

            $imageData = $response->getBody()->getContents();
            $filename = basename(parse_url($url, PHP_URL_PATH));
            $upload = wp_upload_bits($filename, null, $imageData);

            if ($upload['error']) {
                throw new \Exception($upload['error']);
            }

            $attachmentId = $this->createAttachment($upload['file'], $productId);
            $this->generateThumbnails($attachmentId, $upload['file']);

            return $attachmentId;
        } catch (GuzzleException $e) {
            error_log("Image Download Error: " . $e->getMessage());
            return null;
        }
    }

    private function createAttachment(string $file, int $productId): int
    {
        $filetype = wp_check_filetype(basename($file), null);
        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($file)),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $productId
        ];

        $attachId = wp_insert_attachment($attachment, $file, $productId);
        wp_update_attachment_metadata(
            $attachId,
            wp_generate_attachment_metadata($attachId, $file)
        );

        return $attachId;
    }

    private function generateThumbnails(int $attachmentId, string $file): void
    {
        $metadata = wp_generate_attachment_metadata($attachmentId, $file);
        wp_update_attachment_metadata($attachmentId, $metadata);
    }

    private function getExistingAttachments(int $productId): array
    {
        return get_attached_media('image', $productId);
    }
}