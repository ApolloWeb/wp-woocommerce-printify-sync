/**
 * TagsHelper class for Printify Sync plugin
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

class TagsHelper
{
    public static function processTags(array $tags): array
    {
        $processed = [];
        foreach ($tags as $tag) {
            $processed[] = sanitize_text_field((string) $tag);
        }
        return $processed;
    }
}
