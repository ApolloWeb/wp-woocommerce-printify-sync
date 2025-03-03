<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Logs;

class LogCleanup
{
    public static function register()
    {
        add_action('init', [__CLASS__, 'cleanupLogs']);
    }

    public static function cleanupLogs()
    {
        // Logic to clean up logs older than 14 days
    }
}