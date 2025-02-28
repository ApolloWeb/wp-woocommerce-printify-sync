/**
 * CategoriesHelper class for Printify Sync plugin
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

class CategoriesHelper
{
    public static function processCategories(array $categories): array
    {
        $processed = [];
        foreach ($categories as $category) {
            if (isset($category['name'])) {
                $name = sanitize_text_field($category['name']);
                // Optionally limit mapping to categories up to 2 levels deep.
                if (empty($category['parent']) || (!empty($category['level']) && (int) $category['level'] <= 2)) {
                    $processed[] = $name;
                }
            }
        }
        return $processed;
    }
}
