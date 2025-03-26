<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class LoggerService {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpwps_api_logs';
        add_action('init', [$this, 'createLogTable']);
    }

    public function createLogTable(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            status int(3) NOT NULL,
            response_time float NOT NULL,
            rate_limit_remaining int(11),
            rate_limit_reset int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY endpoint (endpoint),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function log(array $data): void {
        global $wpdb;
        
        $wpdb->insert($this->table_name, [
            'endpoint' => $data['endpoint'],
            'method' => $data['method'],
            'status' => $data['status'],
            'response_time' => $data['response_time'],
            'rate_limit_remaining' => $data['rate_limit_remaining'],
            'rate_limit_reset' => $data['rate_limit_reset']
        ]);
    }

    public function getStats(): array {
        global $wpdb;

        $last_24h = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        ));

        $errors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE status >= 400 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        ));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        ));

        return [
            'last_24h' => (int) $last_24h,
            'errors' => (int) $errors,
            'total' => (int) $total
        ];
    }

    public function getLogs(array $filters): array {
        global $wpdb;

        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "status = %d";
            $params[] = $filters['status'];
        }

        if (!empty($filters['endpoint'])) {
            $where[] = "endpoint = %s";
            $params[] = $filters['endpoint'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= %s";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= %s";
            $params[] = $filters['date_to'];
        }

        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $offset = ($filters['page'] - 1) * $filters['per_page'];
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            {$where_clause}
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            array_merge($params, [$filters['per_page'], $offset])
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function getLogTypes(): array {
        global $wpdb;
        
        return $wpdb->get_col("
            SELECT DISTINCT status 
            FROM {$this->table_name} 
            ORDER BY status ASC
        ");
    }

    public function getEndpoints(): array {
        global $wpdb;
        
        return $wpdb->get_col("
            SELECT DISTINCT endpoint 
            FROM {$this->table_name} 
            ORDER BY endpoint ASC
        ");
    }

    public function cleanup(): void {
        global $wpdb;
        
        // Keep logs for 30 days
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        ));
    }
}