<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class StorageManager
{
    private string $currentTime;
    private string $currentUser;
    private bool $r2Enabled;
    private ?string $r2Plugin;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:48:13';
        $this->currentUser = 'ApolloWeb';
        
        // Check for R2 plugins
        $this->r2Plugin = $this->detectR2Plugin();
        $this->r2Enabled = (bool) get_option('wpwps_enable_r2_offload', false);

        // Add compatibility filters
        add_filter('wpwps_handle_upload', [$this, 'handleUpload'], 10, 2);
        add_filter('wpwps_get_attachment_url', [$this, 'getAttachmentUrl'], 10, 2);
    }

    private function detectR2Plugin(): ?string
    {
        if (class_exists('CloudflareR2_Plugin')) {
            return 'cloudflare-r2';
        }
        
        if (defined('AS3CF_PLUGIN_VERSION')) {
            return 'amazon-s3';  // WP Offload Media can work with R2
        }

        return null;
    }

    public function isR2Available(): bool
    {
        return $this->r2Plugin !== null && $this->r2Enabled;
    }

    public function handleUpload(array $file, array $metadata = []): array
    {
        // First, let WordPress handle the upload normally
        $upload = wp_handle_upload($file, ['test_form' => false]);

        if (!empty($upload['error'])) {
            throw new \Exception($upload['error']);
        }

        // If R2 is enabled, trigger the offload
        if ($this->isR2Available()) {
            $this->triggerR2Offload($upload['file'], $metadata);
        }

        return $upload;
    }

    private function triggerR2Offload(string $file, array $metadata = []): void
    {
        switch ($this->r2Plugin) {
            case 'cloudflare-r2':
                // Trigger Cloudflare R2 plugin's offload
                do_action('cloudflare_r2_offload_file', $file, $metadata);
                break;

            case 'amazon-s3':
                // WP Offload Media compatibility
                do_action('as3cf_upload_attachment', $file, $metadata);
                break;
        }

        // Log the offload
        $this->logOffload($file, $metadata);
    }

    public function getAttachmentUrl(int $attachmentId): string
    {
        if ($this->isR2Available()) {
            switch ($this->r2Plugin) {
                case 'cloudflare-r2':
                    $url = apply_filters('cloudflare_r2_get_attachment_url', '', $attachmentId);
                    if ($url) {
                        return $url;
                    }
                    break;

                case 'amazon-s3':
                    $url = apply_filters('as3cf_get_attachment_url', '', $attachmentId);
                    if ($url) {
                        return $url;
                    }
                    break;
            }
        }

        // Fallback to WordPress URL
        return wp_get_attachment_url($attachmentId);
    }

    private function logOffload(string $file, array $metadata): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_storage_log',
            [
                'file_path' => $file,
                'storage_provider' => $this->r2Plugin,
                'metadata' => json_encode($metadata),
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ]
        );
    }
}