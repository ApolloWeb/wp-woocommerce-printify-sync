<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Logger {

    const LOG_DIR = 'logs';

    public static function log($message, $type = 'info') {
        global $wpdb;
        $table = $wpdb->prefix . 'printify_logs';
        $wpdb->insert($table, [
            'date' => current_time('mysql'),
            'type' => $type,
            'message' => $message,
        ]);
    }

    public static function getLogs($search = '', $type = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'printify_logs';
        $query = "SELECT * FROM $table WHERE 1=1";
        if ($search) {
            $query .= $wpdb->prepare(" AND message LIKE %s", '%' . $wpdb->esc_like($search) . '%');
        }
        if ($type) {
            $query .= $wpdb->prepare(" AND type = %s", $type);
        }
        $query .= " ORDER BY date DESC";
        return $wpdb->get_results($query);
    }
}