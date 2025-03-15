<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class R2StorageService
{
    private string $currentTime = '2025-03-15 19:11:19';
    private string $currentUser = 'ApolloWeb';
    private S3Client $client;
    private string $bucket;
    private string $cdnUrl;

    public function __construct()
    {
        $this->initializeClient();
        $this->bucket = get_option('wpwps_r2_bucket');
        $this->cdnUrl = get_option('wpwps_r2_cdn_url');

        // Filter WordPress uploads
        add_filter('wp_handle_upload', [$this, 'handleUpload'], 10, 2);
        add_filter('wp_get_attachment_url', [$this, 'getAttachmentUrl'], 10, 2);
        add_filter('wp_update_attachment_metadata', [$this, 'handleAttachmentMetadata'], 10, 2);
        add_action('delete_attachment', [$this, 'deleteAttachment']);
    }

    private function initializeClient(): void
    {
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => 'https://' . get_option('wpwps_r2_account_id') . '.r2.cloudflarestorage.com',
            'credentials' => [
                'key' => get_option('wpwps_r2_access_key'),
                'secret' => get_option('wpwps_r2_secret_key'),
            ],
            'use_path_style_endpoint' => true
        ]);
    }

    public function uploadImage(string $filePath, string $fileName): string
    {
        try {
            // Generate unique key
            $key = $this->generateUniqueKey($fileName);

            // Upload to R2
            $result = $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'SourceFile' => $filePath,
                'ACL' => 'public-read',
                'ContentType' => mime_content_type($filePath),
                'Metadata' => [
                    'uploaded_by' => $this->currentUser,
                    'upload_date' => $this->currentTime
                ]
            ]);

            // Store upload metadata
            $this->storeUploadMeta($key, $result);

            // Return CDN URL
            return $this->getCdnUrl($key);

        } catch (AwsException $e) {
            error_log("R2 upload failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteImage(string $key): bool
    {
        try {
            // Delete from R2
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key
            ]);

            // Delete thumbnails if they exist
            $this->deleteThumbnails($key);

            return true;

        } catch (AwsException $e) {
            error_log("R2 deletion failed: " . $e->getMessage());
            return false;
        }
    }

    public function handleUpload(array $upload, string $context): array
    {
        // Only handle image uploads
        if (!$this->isImage($upload['type'])) {
            return $upload;
        }

        try {
            // Upload to R2
            $r2Url = $this->uploadImage($upload['file'], basename($upload['file']));

            // Delete local file
            @unlink($upload['file']);

            // Update upload info
            $upload['url'] = $r2Url;
            $upload['r2'] = true;

            return $upload;

        } catch (\Exception $e) {
            error_log("R2 upload handling failed: " . $e->getMessage());
            return $upload;
        }
    }

    public function getAttachmentUrl(string $url, int $attachmentId): string
    {
        $r2Url = get_post_meta($attachmentId, '_wpwps_r2_url', true);
        return $r2Url ?: $url;
    }

    public function handleAttachmentMetadata(array $metadata, int $attachmentId): array
    {
        // Only handle images
        if (!isset($metadata['file']) || !$this->isImage(get_post_mime_type($attachmentId))) {
            return $metadata;
        }

        try {
            // Upload thumbnails to R2
            if (isset($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $size => $sizeData) {
                    $filePath = get_attached_file($attachmentId);
                    $thumbPath = str_replace(basename($filePath), $sizeData['file'], $filePath);

                    if (file_exists($thumbPath)) {
                        // Upload thumbnail
                        $r2Url = $this->uploadImage($thumbPath, $sizeData['file']);
                        
                        // Update metadata
                        $metadata['sizes'][$size]['r2_url'] = $r2Url;
                        
                        // Delete local thumbnail
                        @unlink($thumbPath);
                    }
                }
            }

            return $metadata;

        } catch (\Exception $e) {
            error_log("R2 metadata handling failed: " . $e->getMessage());
            return $metadata;
        }
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $r2Url = get_post_meta($attachmentId, '_wpwps_r2_url', true);
        if ($r2Url) {
            $key = basename($r2Url);
            $this->deleteImage($key);
        }
    }

    private function generateUniqueKey(string $fileName): string
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        
        return sprintf(
            '%s/%s-%s.%s',
            date('Y/m', strtotime($this->currentTime)),
            $name,
            substr(md5(uniqid()), 0, 8),
            $ext
        );
    }

    private function storeUploadMeta(string $key, $result): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_r2_uploads',
            [
                'file_key' => $key,
                'etag' => $result['ETag'],
                'version_id' => $result['VersionId'] ?? null,
                'size' => $result['ContentLength'] ?? 0,
                'mime_type' => $result['ContentType'] ?? null,
                'uploaded_at' => $this->currentTime,
                'uploaded_by' => $this->currentUser
            ]
        );
    }

    private function getCdnUrl(string $key): string
    {
        if ($this->cdnUrl) {
            return rtrim($this->cdnUrl, '/') . '/' . $key;
        }

        return sprintf(
            'https://%s.r2.dev/%s/%s',
            get_option('wpwps_r2_account_id'),
            $this->bucket,
            $key
        );
    }

    private function deleteThumbnails(string $key): void
    {
        $ext = pathinfo($key, PATHINFO_EXTENSION);
        $base = str_replace('.' . $ext, '', $key);

        try {
            $objects = $this->client->listObjects([
                'Bucket' => $this->bucket,
                'Prefix' => $base
            ]);

            foreach ($objects['Contents'] as $object) {
                if ($object['Key'] !== $key) {
                    $this->client->deleteObject([
                        'Bucket' => $this->bucket,
                        'Key' => $object['Key']
                    ]);
                }
            }
        } catch (AwsException $e) {
            error_log("R2 thumbnail deletion failed: " . $e->getMessage());
        }
    }

    private function isImage(string $mimeType): bool
    {
        return strpos($mimeType, 'image/') === 0;
    }
}