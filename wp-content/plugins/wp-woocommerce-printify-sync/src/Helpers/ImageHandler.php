<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\ImageHandlerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Exceptions\ImageHandlingException;

class ImageHandler implements ImageHandlerInterface
{
    private string $currentTime;
    private string $currentUser;
    private string $tempDir;

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime; // 2025-03-15 18:09:19
        $this->currentUser = $currentUser; // ApolloWeb
        $this->tempDir = WP_CONTENT_DIR . '/uploads/wpwps-temp/';
        
        if (!file_exists($this->tempDir)) {
            wp_mkdir_p($this->tempDir);
        }
    }

    public function downloadImage(string $url): string
    {
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        $tmpFile = download_url($url);
        if (is_wp_error($tmpFile)) {
            throw new ImageHandlingException(
                "Failed to download image: {$tmpFile->get_error_message()}"
            );
        }

        $filename = basename($url);
        $localPath = $this->tempDir . uniqid('wpwps_', true) . '_' . $filename;

        if (!rename($tmpFile, $localPath)) {
            @unlink($tmpFile);
            throw new ImageHandlingException("Failed to move temporary file");
        }

        return $localPath;
    }

    public function uploadToMediaLibrary(string $localPath, int $productId, string $title = ''): int
    {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $filename = basename($localPath);
        $upload = wp_upload_bits($filename, null, file_get_contents($localPath));

        if ($upload['error']) {
            throw new ImageHandlingException("Failed to upload image: {$upload['error']}");
        }

        $fileType = wp_check_filetype($upload['file'], null);
        $attachment = [
            'post_mime_type' => $fileType['type'],
            'post_title' => $title ?: pathinfo($filename, PATHINFO_FILENAME),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_author' => get_current_user_id(),
            'meta_input' => [
                '_wpwps_imported_at' => $this->currentTime,
                '_wpwps_imported_by' => $this->currentUser
            ]
        ];

        $attachmentId = wp_insert_attachment($attachment, $upload['file'], $productId);
        if (is_wp_error($attachmentId)) {
            throw new ImageHandlingException(
                "Failed to create attachment: {$attachmentId->get_error_message()}"
            );
        }

        wp_update_attachment_metadata(
            $attachmentId,
            wp_generate_attachment_metadata($attachmentId, $upload['file'])
        );

        return $attachmentId;
    }

    public function attachToProduct(int $productId, int $attachmentId, bool $setAsFeatured = false): void
    {
        $gallery = get_post_meta($productId, '_product_image_gallery', true);
        $galleryArray = $gallery ? explode(',', $gallery) : [];
        $galleryArray[] = $attachmentId;
        
        update_post_meta($productId, '_product_image_gallery', implode(',', array_unique($galleryArray)));

        if ($setAsFeatured) {
            set_post_thumbnail($productId, $attachmentId);
        }
    }

    public function cleanup(string $localPath): void
    {
        if (file_exists($localPath)) {
            @unlink($localPath);
        }
    }
}