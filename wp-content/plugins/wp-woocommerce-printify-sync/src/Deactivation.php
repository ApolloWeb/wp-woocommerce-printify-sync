<?php
/**
 * Plugin deactivation handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;

/**
 * Handles plugin deactivation tasks.
 */
class Deactivation
{
    /**
     * Runs on plugin deactivation.
     *
     * @return void
     */
    public static function deactivate()
    {
        // Clear scheduled cron events
        self::clearCronJobs();

        // Log deactivation
        $logger = new Logger();
        $logger->info('Plugin deactivated');
    }

    /**
     * Clear scheduled cron jobs.
     *
     * @return void
     */
    private static function clearCronJobs()
    {
        // Clear the product sync cron job
        $timestamp = wp_next_scheduled('wpwps_sync_products');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wpwps_sync_products');
        }

        // Clear the email queue processing cron job
        $timestamp = wp_next_scheduled('wpwps_process_email_queue');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wpwps_process_email_queue');
        }
    }
}
