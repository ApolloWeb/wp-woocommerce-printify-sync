/**
 * VariantsHelper class for Printify Sync plugin
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

class VariantsHelper
{
    public static function processVariants(array $variants): array
    {
        $processed = [];
        foreach ($variants as $variant) {
            $processed[] = [
                'sku'        => sanitize_text_field($variant['sku'] ?? ''),
                'price'      => isset($variant['price']) ? floatval($variant['price']) : 0.0,
                'attributes' => $variant['attributes'] ?? [],
            ];
        }
        return $processed;
    }
}
