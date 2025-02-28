/**
 * ImagesHelper class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 * Time: 02:20:39
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ImagesHelper
{
    public static function processImages(array $images): array
    {
        $processed = [
            'main'    => 0,
            'gallery' => [],
        ];

        if (empty($images)) {
            return $processed;
        }

        // Use first image as the main image.
        $first = array_shift($images);
        $processed['main'] = ImageUpload::uploadAndOptimize((string) $first);

        // Process remaining images.
        foreach ($images as $imageUrl) {
            $processed['gallery'][] = ImageUpload::uploadAndOptimize((string) $imageUrl);
        }

        return $processed;
    }
}
