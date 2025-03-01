<?php
/**
 * Logger utility for logging messages.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utilities;

class Logger {

    /**
     * Log a message.
     *
     * @param string $type    The type of the log message.
     * @param string $message The log message.
     * @param string $status  The status of the log message.
     */
    public static function log($type, $message, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwps_sync_logs';

        $wpdb->insert(
            $table_name,
            array(
                'time'    => current_time('mysql'),
                'type'    => $type,
                'message' => $message,
                'status'  => $status,
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );
    }
}