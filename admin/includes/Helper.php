/**
 * Helper class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 * Time: 02:20:39
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

namespace ApolloWeb\WooCommercePrintifySync;

if (!defined('ABSPATH')) {
    exit;
}

class Helper
{
    private static $option_api_key = 'printify_api_key';
    private static $option_shop_id = 'printify_selected_shop';

    public static function getApiKey()
    {
        return trim(get_option(self::$option_api_key, ''));
    }

    public static function setApiKey($apiKey)
    {
        update_option(self::$option_api_key, sanitize_text_field($apiKey));
    }

    public static function getShopId()
    {
        return trim(get_option(self::$option_shop_id, ''));
    }

    public static function setShopId($shopId)
    {
        update_option(self::$option_shop_id, sanitize_text_field($shopId));
    }

    public static function getApi()
    {
        $apiKey = self::getApiKey();
        return !empty($apiKey) ? new Api($apiKey) : null;
    }
}
