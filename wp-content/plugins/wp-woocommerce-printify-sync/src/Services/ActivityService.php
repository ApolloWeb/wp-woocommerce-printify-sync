<?php
/**
 * Activity service for handling activity logs.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Activity service for centralized activity logging.
 */
class ActivityService
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;
    
    /**
     * Database table name.
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Constructor.
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger)
    {
        global $wpdb;
        
        $this->logger = $logger;
        $this->table_name = $wpdb->prefix . 'wpwps_activity_log';
    }
    
    /**
     * Initialize the service.
     *
     * @return void
     */
    public function init()
    {
        add_action('wp_ajax_wpwps_get_activities', [$this, 'getActivitiesAjax']);
        add_action('wp_ajax_wpwps_clear_activities', [$this, 'clearActivitiesAjax']);
    }
    
    /**
     * Log an activity.
     *
     * @param string $type    Activity type (product_sync, order_sync, api_connection, etc.).
     * @param string $message Activity message.
     * @param array  $data    Additional data to log.
     * @return int|false Activity ID if added, false on failure.
     */
    public function log($type, $message, $data = [])
    {
        global $wpdb;
        
        // Log to debug as well
        $this->logger->debug("Activity: {$message}", [
            'type' => $type,
            'data' => $data,
        ]);
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'type' => $type,
                'message' => $message,
                'data' => !empty($data) ? wp_json_encode($data) : null,
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql'),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
            ]
        );
        
        if ($result === false) {
            $this->logger->error('Failed to log activity: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get recent activities.
     *
     * @param int    $limit Maximum number of activities to get.
     * @param string $type  Filter by activity type.
     * @return array Activities.
     */
    public function getActivities($limit = 10, $type = '')
    {
        global $wpdb;
        
        $query = "SELECT * FROM {$this->table_name}";
        $params = [];
        
        if (!empty($type)) {
            $query .= " WHERE type = %s";
            $params[] = $type;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $activities = $wpdb->get_results($query, ARRAY_A);
        
        return is_array($activities) ? $activities : [];
    }
    
    /**
     * Get activities via AJAX.
     *
     * @return void
     */
    public function getActivitiesAjax()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }
        
        $limit = isset($_GET['limit']) ? absint($_GET['limit']) : 10;
        $type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
        
        $activities = $this->getActivities($limit, $type);
        
        // Format activities for display
        $formatted_activities = [];
        foreach ($activities as $activity) {
            $formatted_activities[] = [
                'id' => $activity['id'],
                'type' => $activity['type'],
                'message' => $activity['message'],
                'data' => !empty($activity['data']) ? json_decode($activity['data'], true) : null,
                'user_id' => $activity['user_id'],
                'created_at' => $activity['created_at'],
                'relative_time' => $this->getRelativeTime($activity['created_at']),
                'icon' => $this->getActivityIcon($activity['type']),
            ];
        }
        
        wp_send_json_success([
            'activities' => $formatted_activities,
        ]);
    }
    
    /**
     * Clear activities via AJAX.
     *
     * @return void
     */
    public function clearActivitiesAjax()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
        
        $result = $this->clearActivities($type);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Activities cleared successfully.', 'wp-woocommerce-printify-sync'),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to clear activities.', 'wp-woocommerce-printify-sync'),
            ]);
        }
    }
    
    /**
     * Clear activities.
     *
     * @param string $type Filter by activity type.
     * @return bool Whether the operation was successful.
     */
    public function clearActivities($type = '')
    {
        global $wpdb;
        
        $query = "TRUNCATE TABLE {$this->table_name}";
        
        if (!empty($type)) {
            $query = $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE type = %s",
                $type
            );
        }
        
        $result = $wpdb->query($query);
        
        return $result !== false;
    }
    
    /**
     * Get relative time (e.g., "2 hours ago").
     *
     * @param string $datetime Datetime string.
     * @return string Relative time.
     */
    private function getRelativeTime($datetime)
    {
        $time = strtotime($datetime);
        $now = current_time('timestamp');
        $diff = $now - $time;
        
        if ($diff < 60) {
            return __('Just now', 'wp-woocommerce-printify-sync');
        }
        
        if ($diff < 3600) {
            $mins = round($diff / 60);
            return sprintf(
                _n('%s minute ago', '%s minutes ago', $mins, 'wp-woocommerce-printify-sync'),
                $mins
            );
        }
        
        if ($diff < 86400) {
            $hours = round($diff / 3600);
            return sprintf(
                _n('%s hour ago', '%s hours ago', $hours, 'wp-woocommerce-printify-sync'),
                $hours
            );
        }
        
        if ($diff < 604800) {
            $days = round($diff / 86400);
            return sprintf(
                _n('%s day ago', '%s days ago', $days, 'wp-woocommerce-printify-sync'),
                $days
            );
        }
        
        if ($diff < 2592000) {
            $weeks = round($diff / 604800);
            return sprintf(
                _n('%s week ago', '%s weeks ago', $weeks, 'wp-woocommerce-printify-sync'),
                $weeks
            );
        }
        
        if ($diff < 31536000) {
            $months = round($diff / 2592000);
            return sprintf(
                _n('%s month ago', '%s months ago', $months, 'wp-woocommerce-printify-sync'),
                $months
            );
        }
        
        $years = round($diff / 31536000);
        return sprintf(
            _n('%s year ago', '%s years ago', $years, 'wp-woocommerce-printify-sync'),
            $years
        );
    }
    
    /**
     * Get activity icon class based on type.
     *
     * @param string $type Activity type.
     * @return string FontAwesome icon class.
     */
    private function getActivityIcon($type)
    {
        switch ($type) {
            case 'product_sync':
                return 'fas fa-sync-alt';
            case 'order_sync':
                return 'fas fa-shopping-cart';
            case 'order_status':
                return 'fas fa-shipping-fast';
            case 'api_connection':
                return 'fas fa-plug';
            case 'settings':
                return 'fas fa-cog';
            case 'error':
                return 'fas fa-exclamation-triangle';
            case 'ticket':
                return 'fas fa-ticket-alt';
            default:
                return 'fas fa-info-circle';
        }
    }

    /**
     * Get icon class for a given activity type.
     *
     * @param string $type Activity type.
     * @return string Icon class.
     */
    public function getActivityIcon($type)
    {
        $icon_map = [
            'product_sync' => 'fas fa-box',
            'order_sync' => 'fas fa-shopping-cart',
            'order_status' => 'fas fa-truck',
            'shipping_profile' => 'fas fa-shipping-fast',
            'ticket' => 'fas fa-ticket-alt',
            'settings' => 'fas fa-cog',
            'api' => 'fas fa-plug',
            'error' => 'fas fa-exclamation-triangle',
        ];
        
        return isset($icon_map[$type]) ? $icon_map[$type] : 'fas fa-info-circle';
    }
}
