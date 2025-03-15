<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImageTrackingService
{
    private string $currentTime;
    private string $currentUser;

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime; // 2025-03-15 18:14:34
        $this->currentUser = $currentUser; // ApolloWeb
    }

    public function hasImageChanged(string $imageUrl, int $attachmentId): bool
    {
        $storedHash = get_post_meta($attachmentId, '_printify_image_hash', true);
        $storedUrl = get_post_meta($attachmentId, '_printify_image_url', true);

        if ($storedUrl !== $imageUrl) {
            return true;
        }

        try {
            $currentHash = $this->getImageHash($imageUrl);
            return $storedHash !== $currentHash;
        } catch (\Exception $e) {
            $this->logError('Image hash check failed', [
                'image_url' => $imageUrl,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage()
            ]);
            return true; // Assume changed if we can't verify
        }
    }

    public function trackImage(string $imageUrl, int $attachmentId): void
    {
        try {
            $hash = $this->getImageHash($imageUrl);
            update_post_meta($attachmentId, '_printify_image_hash', $hash);
            update_post_meta($attachmentId, '_printify_image_url', $imageUrl);
            update_post_meta($attachmentId, '_printify_image_updated_at', $this->currentTime);
            update_post_meta($attachmentId, '_printify_image_updated_by', $this->currentUser);
            
            $this->logImageTracking($attachmentId, $imageUrl, $hash);
        } catch (\Exception $e) {
            $this->logError('Image tracking failed', [
                'image_url' => $imageUrl,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getImageHash(string $url): string
    {
        $headers = get_headers($url, 1);
        if (isset($headers['ETag'])) {
            return trim($headers['ETag'], '"');
        }
        
        $content = file_get_contents($url);
        return md5($content);
    }

    private function logImageTracking(int $attachmentId, string $url, string $hash): void
    {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wpwps_image_tracking_log',
            [
                'attachment_id' => $attachmentId,
                'image_url' => $url,
                'image_hash' => $hash,
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }

    private function logError(string $message, array $context = []): void
    {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wpwps_error_log',
            [
                'message' => $message,
                'context' => json_encode($context),
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ],
            ['%s', '%s', '%s', '%s']
        );
    }
}