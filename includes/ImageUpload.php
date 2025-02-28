/**
 * ImageUpload class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ImageUpload
{
    public static function uploadAndOptimize(string $imageUrl): int
    {
        $attachmentId = self::uploadImage($imageUrl);
        if ($attachmentId) {
            do_action('smush_it', $attachmentId);
        }
        return $attachmentId;
    }

    protected static function uploadImage(string $imageUrl): int
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($imageUrl);
        if (is_wp_error($tmp)) {
            return 0;
        }

        $fileArray = [
            'name'     => basename($imageUrl),
            'tmp_name' => $tmp,
        ];

        $attachmentId = media_handle_sideload($fileArray, 0);
        if (is_wp_error($attachmentId)) {
            @unlink($fileArray['tmp_name']);
            return 0;
        }
        return $attachmentId;
    }
}
