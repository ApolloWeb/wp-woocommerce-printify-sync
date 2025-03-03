<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Logs;

class Logger
{
    public static function log($message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($message);
        }
    }
}