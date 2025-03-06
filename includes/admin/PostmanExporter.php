<?php
/**
 * Postman Exporter
 *
 * Handles live data export functionalities for the Postman API Tester.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\API\Printify\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PostmanExporter {
    /**
     * API client
     * 
     * @var PrintifyApiClient
     */
    private $api_client = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_client = PrintifyApiClient::get_instance();
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwprintifysync_export_data', array($this, 'ajax_export_data'));
        add_action('wp_ajax_wpwprintifysync_schedule_export', array($this, 'ajax_schedule_export'));
    }
    
    /**
     * AJAX handler for data export
     */
    public function ajax_export_data() {
        // Verify nonce
        check_ajax_referer('wpwprintifysync-postman-nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to export data.', 'wp-woocommerce-printify-sync')));
        }
        
        $endpoint = isset($_POST['endpoint']) ? sanitize_text_field($_POST['endpoint']) : '';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json';
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        
        if (empty($endpoint)) {
            wp_send_json_error(array('message' => __('No endpoint specified for export.', 'wp-woocommerce-printify-sync')));
        }
        
        try {
            // Get data from API based on endpoint
            $response = $this->fetch_export_data($endpoint, $filters);
            
            // Process data based on format
            $processed_data = $this->process_data_for_export($response, $format);
            
            // Generate download URL
            $file_url = $this->generate_export_file($processed_data, $format, $endpoint);
            
            wp_send_json_success(array(
                'message' => __('Export completed successfully!', 'wp-woocommerce-printify-sync'),
                'download_url' => $file_url,
                'record_count' => is_array($response) ? count($response) : 1
            ));
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => sprintf(__('Export failed: %s', 'wp-woocommerce-printify-sync'), $e->getMessage())
            ));
        }
    }
    
    /**
     * AJAX handler for scheduling exports
     */
    public function ajax_schedule_export() {
        // Verify nonce
        check_ajax_referer('wpwprintifysync-postman-nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to schedule exports.', 'wp-woocommerce-printify-sync')));
        }
        
        $schedule_name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $endpoint = isset($_POST['endpoint']) ? sanitize_text_field($_POST['endpoint']) : '';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json';
        $frequency = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : 'daily';
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $notification_email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (empty($schedule_name) || empty($endpoint)) {
            wp_send_json_error(array('message' => __('Missing required fields for scheduled export.', 'wp-woocommerce-printify-sync')));
        }
        
        // Save schedule configuration
        $schedules = get_option('wpwprintifysync_export_schedules', array());
        $schedule_id = 'schedule_' . time();
        
        $schedules[$schedule_id] = array(
            'name' => $schedule_name,
            'endpoint' => $endpoint,
            'format' => $format,
            'frequency' => $frequency,
            'filters' => $filters,
            'notification_email' => $notification_email,
            'created' => current_time('mysql'),
            'last_run' => '',
            'next_run' => $this->calculate_next_run_time($frequency)
        );
        
        update_option('wpwprintifysync_export_schedules', $schedules);
        
        // Schedule the export
        wp_schedule_single_event(
            strtotime($schedules[$schedule_id]['next_run']),
            'wpwprintifysync_scheduled_export',
            array($schedule_id)
        );
        
        wp_send_json_success(array(
            'message' => __('Export scheduled successfully!', 'wp-woocommerce-printify-sync'),
            'schedule_id' => $schedule_id,
            'next_run' => $schedules[$schedule_id]['next_run']
        ));
    }
    
    /**
     * Fetch data for export
     *
     * @param string $endpoint API endpoint
     * @param array $filters Optional filters
     * @return array|object API response data
     */
    private function fetch_export_data($endpoint, $filters = array()) {
        // Logic to fetch data based on endpoint and filters
        // Implementation depends on your API client structure
        
        return $this->api_client->get($endpoint, $filters);
    }
    
    /**
     * Process data for export in specified format
     *
     * @param array|object $data Raw API response data
     * @param string $format Export format (json, csv, xml, pdf)
     * @return string|array Processed data
     */
    private function process_data_for_export($data, $format) {
        switch ($format) {
            case 'csv':
                return $this->convert_to_csv($data);
            
            case 'xml':
                return $this->convert_to_xml($data);
                
            case 'pdf':
                return $this->convert_to_pdf($data);
                
            case 'json':
            default:
                return json_encode($data, JSON_PRETTY_PRINT);
        }
    }
    
    /**
     * Generate export file and return download URL
     *
     * @param string|array $data Processed export data
     * @param string $format Export format
     * @param string $endpoint API endpoint used for export
     * @return string Download URL
     */
    private function generate_export_file($data, $format, $endpoint) {
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/printify-exports';
        
        // Create directory if it doesn't exist
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
            
            // Add index.php to prevent directory listing
            file_put_contents($export_dir . '/index.php', '<?php // Silence is golden.');
            
            // Add .htaccess for additional security
            file_put_contents($export_dir . '/.htaccess', 'Deny from all');
        }
        
        // Generate unique filename
        $filename = 'printify-export-' . sanitize_title($endpoint) . '-' . date('Ymd-His') . '.' . $format;
        $filepath = $export_dir . '/' . $filename;
        
        // Write data to file
        file_put_contents($filepath, $data);
        
        // Create secure token for download
        $token = md5($filename . time() . wp_rand());
        set_transient('printify_export_' . $token, $filepath, 60 * 60); // Valid for 1 hour
        
        // Return download URL with token
        return add_query_arg(array(
            'action' => 'wpwprintifysync_download_export',
            'token' => $token,
            '_wpnonce' => wp_create_nonce('download_export')
        ), admin_url('admin-ajax.php'));
    }
    
    /**
     * Calculate next run time based on frequency
     *
     * @param string $frequency Schedule frequency
     * @return string Date/time string
     */
    private function calculate_next_run_time($frequency) {
        switch ($frequency) {
            case 'hourly':
                return date('Y-m-d H:i:s', strtotime('+1 hour'));
                
            case 'daily':
                return date('Y-m-d H:i:s', strtotime('+1 day'));
                
            case 'weekly':
                return date('Y-m-d H:i:s', strtotime('+1 week'));
                
            case 'monthly':
                return date('Y-m-d H:i:s', strtotime('+1 month'));
                
            default:
                return date('Y-m-d H:i:s', strtotime('+1 day'));
        }
    }
    
    /**
     * Convert data to CSV format
     *
     * @param array|object $data Raw data
     * @return string CSV data
     */
    private function convert_to_csv($data) {
        if (!is_array($data)) {
            $data = array($data);
        }
        
        // Get headers from first item keys
        $headers = array();
        if (!empty($data)) {
            $first_item = reset($data);
            if (is_object($first_item)) {
                $first_item = get_object_vars($first_item);
            }
            $headers = array_keys($first_item);
        }
        
        // Start CSV output
        $output = fopen('php://temp', 'r+');
        
        // Add headers
        fputcsv($output, $headers);
        
        // Add rows
        foreach ($data as $row) {
            if (is_object($row)) {
                $row = get_object_vars($row);
            }
            
            // Ensure all headers are included
            $csv_row = array();
            foreach ($headers as $header) {
                $csv_row[] = isset($row[$header]) ? $row[$header] : '';
            }
            
            fputcsv($output, $csv_row);
        }
        
        // Get contents
        rewind($output);
        $csv_data = stream_get_contents($output);
        fclose($output);
        
        return $csv_data;
    }
    
    /**
     * Convert data to XML format
     *
     * @param array|object $data Raw data
     * @return string XML data
     */
    private function convert_to_xml($data) {
        // Implementation for XML conversion
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><data></data>');
        $this->array_to_xml($data, $xml);
        return $xml->asXML();
    }
    
    /**
     * Helper function to convert array to XML
     *