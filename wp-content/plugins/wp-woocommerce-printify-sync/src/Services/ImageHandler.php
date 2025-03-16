<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\ImageHandlerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\StorageInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class ImageHandler extends AbstractService implements ImageHandlerInterface
{
    private StorageInterface $storage;
    private array $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    private int $maxFileSize = 5242880; // 5MB

    public function __construct(
        StorageInterface $storage,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        parent::__construct($logger, $config);
        $this->storage = $storage;
    }

    public function downloadImage(string $url): ?string
    {
        try {
            $response = wp_remote_get($url);
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $contentType = wp_remote_retrieve_header($response, 'content-type');
            if (!in_array($contentType, $this->allowedMimes)) {
                throw new \Exception("Invalid image type: {$contentType}");
            }

            $content = wp_remote_retrieve_body($response);
            if (strlen($content) > $this->maxFileSize) {
                throw new \Exception("File size exceeds limit");
            }

            $tempFile = wp_tempnam();
            if (file_put_contents($tempFile, $content) === false) {
                throw new \Exception("Failed to save temporary file");
            }

            return $tempFile;

        } catch (\Exception $e) {
            $this->logError('downloadImage', $e, [
                'url' => $url
            ]);
            return null;
        }
    }

    public function optimizeImage(string $path): ?string
    {
        try {
            if (!function_exists('wp_get_image_editor')) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
            }

            $editor = wp_get_image_editor($path);
            if (is_wp_error($editor)) {
                throw new \Exception($editor->get_error_message());
            }

            // Set quality
            $editor->set_quality(90);

            // Resize if needed
            $maxSize = (int)$this->config->get('max_image_size', 2048);
            $size = getimagesize($path);
            if ($size[0] > $maxSize || $size[1] > $maxSize) {
                $editor->resize($maxSize, $maxSize, false);
            }

            // Save optimized image
            $tempFile = wp_tempnam();
            $result = $editor->save($tempFile);
            
            if (is_wp_error($result)) {
                throw new \Exception($result->get_error_message());
            }

            return $tempFile;

        } catch (\Exception $e) {
            $this->logError('optimizeImage', $e, [
                'path' => $path
            ]);
            return null;
        }
    }

    public function uploadToWordPress(string $path, int $postId): ?int
    {
        try {
            $upload = wp_upload_bits(basename($path), null, file_get_contents($path));
            if ($upload['error']) {
                throw new \Exception($upload['error']);
            }

            $fileType = wp_check_filetype(basename($path), null);
            $attachment = [
                'post_mime_type' => $fileType['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($path)),
                'post_content' => '',
                'post_status' => 'inherit'
            ];

            $attachId = wp_insert_attachment($attachment, $upload['file'], $postId);
            if (is_wp_error($attachId)) {
                throw new \Exception($attachId->get_error_message());
            }

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachData = wp_generate_attachment_metadata($attachId, $upload['file']);
            wp_update_attachment_metadata($attachId, $attachData);

            return $attachId;

        } catch (\Exception $e) {
            $this->logError('uploadToWordPress