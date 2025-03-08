<?php

namespace ApolloWeb\WpWooCommercePrintifySync\Helpers;

class Logger
{
    /**
     * Log a message to the WordPress debug log.
     *
     * @param string $message
     * @return void
     */
    public static function log($message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($message);
        }
    }
}