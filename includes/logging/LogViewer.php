        $sql = "SELECT * FROM {$this->table_name} WHERE 1=1";
        $args = array();
        
        if (!empty($level)) {
            $sql .= " AND level = %s";
            $args[] = $level;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $args[] = $limit;
        
        if (!empty($args)) {
            $sql = $wpdb->prepare($sql, $args);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Clear logs
     *
     * @param string $level Log level filter
     * @param string $date_before Delete logs before this date
     * @return int Number of logs deleted
     */
    public function clear_logs($level = '', $date_before = '') {
        global $wpdb;
        
        $sql = "DELETE FROM {$this->table_name} WHERE 1=1";
        $args = array();
        
        // Add filters
        if (!empty($level)) {
            $sql .= " AND level = %s";
            $args[] = $level;
        }
        
        if (!empty($date_before)) {
            $sql .= " AND created_at < %s";
            $args[] = $date_before;
        }
        
        if (!empty($args)) {
            $sql = $wpdb->prepare($sql, $args);
        }
        
        $result = $wpdb->query($sql);
        
        return $result;
    }
    
    /**
     * Purge old logs based on retention setting
     */
    public function purge_old_logs() {
        $retention_days = get_option('wpwprintifysync_settings')['log_retention'] ?? 30;
        
        if (empty($retention_days) || !is_numeric($retention_days) || $retention_days < 1) {
            $retention_days = 30; // Default to 30 days
        }
        
        $date_before = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        $deleted = $this->clear_logs('', $date_before);
        
        if ($deleted > 0) {
            Logger::get_instance()->info('Purged old logs', array(
                'count' => $deleted,
                'retention_days' => $retention_days,
                'timestamp' => $this->timestamp
            ));
        }
    }
    
    /**
     * AJAX handler for clearing logs
     */
    public function ajax_clear_logs() {
        // Check nonce
        check_ajax_referer('wpwprintifysync-logs-nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync')));
            return;
        }
        
        // Get parameters
        $level = isset($_POST['level']) ? sanitize_text_field($_POST['level']) : '';
        $date_before = isset($_POST['date_before']) ? sanitize_text_field($_POST['date_before']) : '';
        
        // Clear logs
        $deleted = $this->clear_logs($level, $date_before);
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d logs deleted successfully!', 'wp-woocommerce-printify-sync'), $deleted),
            'count' => $deleted
        ));
    }
    
    /**
     * Export logs to CSV
     *
     * @param string $level Log level filter
     * @param string $search Search term
     * @param string $date_from Start date filter
     * @param string $date_to End date filter
     * @return string CSV content
     */
    public function export_logs_csv($level = '', $search = '', $date_from = '', $date_to = '') {
        global $wpdb;
        
        $sql = "SELECT id, level, message, context, created_at FROM {$this->table_name} WHERE 1=1";
        $args = array();
        
        // Add filters
        if (!empty($level)) {
            $sql .= " AND level = %s";
            $args[] = $level;
        }
        
        if (!empty($search)) {
            $sql .= " AND (message LIKE %s OR context LIKE %s)";
            $args[] = '%' . $wpdb->esc_like($search) . '%';
            $args[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        if (!empty($date_from)) {
            $sql .= " AND created_at >= %s";
            $args[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $sql .= " AND created_at <= %s";
            $args[] = $date_to . ' 23:59:59';
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (!empty($args)) {
            $sql = $wpdb->prepare($sql, $args);
        }
        
        $logs = $wpdb->get_results($sql, ARRAY_A);
        
        if (empty($logs)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Add CSV header
        fputcsv($output, array('ID', 'Level', 'Message', 'Context', 'Date'));
        
        // Add data rows
        foreach ($logs as $log) {
            $context = $log['context'];
            
            // Format context as readable string
            if (is_serialized($context)) {
                $context = maybe_unserialize($context);
            } elseif (is_string($context) && $this->is_json($context)) {
                $context = json_decode($context, true);
            }
            
            if (is_array($context)) {
                $context = wp_json_encode($context, JSON_PRETTY_PRINT);
            }
            
            fputcsv($output, array(
                $log['id'],
                $log['level'],
                $log['message'],
                $context,
                $log['created_at']
            ));
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Check if string is JSON
     *
     * @param string $string String to check
     * @return bool Is valid JSON
     */
    private function is_json($string) {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}