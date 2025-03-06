<?php
/**
 * Visitor Tracker - Tracks visitor country and other metrics
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Analytics
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Analytics;

use ApolloWeb\WPWooCommercePrintifySync\Geolocation\Geolocator;
use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

class VisitorTracker {
    private static $instance = null;
    private $geolocator;
    private $timestamp = '2025-03-05 19:22:34';
    private $user = 'ApolloWeb';
    
    /**
     * Get single instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->geolocator = \ApolloWeb\WPWooCommercePrintifySync\Geolocation\Geolocator::getInstance();
        
        // Track visitor on wp_loaded for frontend requests
        add_action('wp_loaded', [$this, 'trackVisitor']);
        
        // Add cleanup task
        add_action('wpwprintifysync_daily_cleanup', [$this, 'cleanupOldRecords']);
    }
    
    /**
     * Setup database table
     */
    public function setupTable() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwprintifysync_visitor_stats';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            visit_date date NOT NULL,
            country_code varchar(2) NOT NULL,
            currency_code varchar(3) NOT NULL,
            visit_count int(11) NOT NULL DEFAULT 1,
            conversion_count int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY date_country (visit_date, country_code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Track visitor
     */
    public function trackVisitor() {
        // Skip admin, AJAX, API requests
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        
        // Skip logged-in users optionally
        if (is_user_logged_in() && get_option('wpwprintifysync_skip_logged_in', false)) {
            return;
        }
        
        // Get visitor country
        $country_code = $this->geolocator->getUserCountry();
        $currency_code = $this->geolocator->getUserCurrency();
        
        // Record visit
        $this->recordVisit($country_code, $currency_code);
    }
    
    /**
     * Record visitor's country
     */
    private function recordVisit($country_code, $currency_code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwprintifysync_visitor_stats';
        
        // Use current date
        $visit_date = date('Y-m-d');
        
        // Try to update existing record first
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_name (visit_date, country_code, currency_code, visit_count)
            VALUES (%s, %s, %s, 1)
            ON DUPLICATE KEY UPDATE visit_count = visit_count + 1",
            $visit_date,
            $country_code,
            $currency_code
        ));
        
        if ($result === false) {
            Logger::getInstance()->error('Failed to record visitor', [
                'country' => $country_code,
                'currency' => $currency_code,
                'error' => $wpdb->last_error
            ]);
        }
    }
    
    /**
     * Record conversion (order placed)
     */
    public function recordConversion($country_code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwprintifysync_visitor_stats';
        
        // Use current date
        $visit_date = date('Y-m-d');
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name 
            SET conversion_count = conversion_count + 1
            WHERE visit_date = %s AND country_code = %s",
            $visit_date,
            $country_code
        ));
    }
    
    /**
     * Get visitor stats by country
     */
    public function getVisitorStatsByCountry($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwprintifysync_visitor_stats';
        
        $start_date = date('Y-m-d', strtotime("-$days days"));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                country_code,
                SUM(visit_count) as visits,
                SUM(conversion_count) as conversions
            FROM $table_name
            WHERE visit_date >= %s
            GROUP BY country_code
            ORDER BY visits DESC",
            $start_date
        ));
        
        return $results;
    }
    
    /**
     * Get visitor stats by date
     */
    public function getVisitorStatsByDate($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwprintifysync_visitor_stats';
        
        $start_date = date('Y-m-d', strtotime("-$days days"));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                visit_date,
                SUM(visit_count) as visits,
                SUM(conversion_count) as conversions
            FROM $table_name
            WHERE visit_date >= %s
            GROUP BY visit_date
            ORDER BY visit_date ASC",
            $start_date
        ));
        
        return $results;
    }
    
    /**
     * Get currency usage stats
     */
    public function getCurrencyStats($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwprintifysync_visitor_stats';
        
        $start_date = date('Y-m-d', strtotime("-$days days"));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                currency_code,
                SUM(visit_count) as visits,
                SUM(conversion_count) as conversions
            FROM $table_name
            WHERE visit_date >= %s
            GROUP BY currency_code
            ORDER BY visits DESC",
            $start_date
        ));
        
        return $results;
    }
    
    /**
     * Clean up old records
     */
    public function cleanupOldRecords($days_to_keep = 365) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwprintifysync_visitor_stats';
        
        $cutoff_date = date('Y-m-d', strtotime("-$days_to_keep days"));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE visit_date < %s",
            $cutoff_date
        ));
        
        Logger::getInstance()->info('Cleaned up visitor stats', [
            'days_kept' => $days_to_keep,
            'cutoff_date' => $cutoff_date
        ]);
    }
}