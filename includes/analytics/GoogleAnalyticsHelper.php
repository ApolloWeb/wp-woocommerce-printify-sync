<?php
/**
 * Google Analytics Helper
 *
 * Helper class for Google Analytics integration providing simplified tracking methods.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Analytics
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Analytics;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class GoogleAnalyticsHelper {
    /**
     * Singleton instance
     *
     * @var GoogleAnalyticsHelper
     */
    private static $instance = null;
    
    /**
     * Google Analytics integration instance
     *
     * @var GoogleAnalyticsIntegration
     */
    private $ga = null;
    
    /**
     * Settings array
     * 
     * @var array
     */
    private $settings = array();
    
    /**
     * Is debug mode enabled
     *
     * @var bool
     */
    private $debug = false;
    
    /**
     * Get singleton instance
     *
     * @return GoogleAnalyticsHelper
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->ga = GoogleAnalyticsIntegration::get_instance();
        $this->settings = get_option('wpwprintifysync_settings', array());
        $this->debug = isset($this->settings['ga_debug']) && $this->settings['ga_debug'] === 'yes';
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Add helper tracking methods to hooks
        add_action('wpwprintifysync_product_imported', array($this, 'track_product_import'), 10, 2);
        add_action('wpwprintifysync_product_updated', array($this, 'track_product_update'), 10, 2);
        add_action('wpwprintifysync_product_sync_failed', array($this, 'track_product_sync_failure'), 10, 3);
        add_action('wpwprintifysync_order_sent', array($this, 'track_order_sent'), 10, 3);
        add_action('wpwprintifysync_order_status_changed', array($this, 'track_order_status_change'), 10, 3);
        
        // Track admin-specific events
        if (is_admin()) {
            add_action('wpwprintifysync_settings_saved', array($this, 'track_settings_update'));
            add_action('wpwprintifysync_bulk_product_import', array($this, 'track_bulk_product_import'), 10, 2);
            add_action('wpwprintifysync_bulk_order_sync', array($this, 'track_bulk_order_sync'), 10, 2);
        }
    }
    
    /**
     * Track product import
     *
     * @param int $product_id WooCommerce product ID
     * @param array $printify_data Printify product data
     */
    public function track_product_import($product_id, $printify_data) {
        if (!$this->is_tracking_enabled()) {
            return;
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return;
        }
        
        $event_data = array(
            'event_name' => 'product_import',
            'event_category' => 'Printify',
            'event_label' => $product->get_name(),
            'product_id' => $product_id,
            'printify_id' => $printify_data['id'] ?? '',
            'product_name' => $product->get_name(),
            'product_type' => $product->get_type()
        );
        
        $this->send_event_tracking($event_data);
        
        if ($this->debug) {
            Logger::get_instance()->debug('GA Helper: Tracked product import', $event_data);
        }
    }
    
    /**
     * Track product update
     *
     * @param int $product_id WooCommerce product ID
     * @param array $printify_data Printify product data
     */
    public function track_product_update($product_id, $printify_data) {
        if (!$this->is_tracking_enabled()) {
            return;
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return;
        }
        
        $event_data = array(
            'event_name' => 'product_update',
            'event_category' => 'Printify',
            'event_label' => $product->get_name(),
            'product_id' => $product_id,
            'printify_id' => $printify_data['id'] ?? '',
            'product_name' => $product->get_name(),
            'product_type' => $product->get_type()
        );
        
        $this->send_event_tracking($event_data);
        
        if ($this->debug) {
            Logger::get_instance()->debug('GA Helper: Tracked product update', $event_data);
        }
    }
    
    /**
     * Track product sync failure
     *
     * @param int $product_id WooCommerce product ID
     * @param string $error_message Error message
     * @param array $context Error context
     */
    public function track_product_sync_failure($product_id, $error_message, $context = array()) {
        if (!$this->is_tracking_enabled()) {
            return;
        }
        
        $product = wc_get_product($product_id);
        $product_name = $product ? $product->get_name() : 'Unknown Product';
        
        $event_data = array(
            'event_name' => 'product_sync_error',
            'event_category' => 'Printify',
            'event_label' => $error_message,
            'product_id' => $product_id,
            'product_name' => $product_name,
            'error_message' => $error_message
        );
        
        $this->send_event_tracking($event_data);
        
        if ($this->debug) {
            Logger::get_instance()->debug('GA Helper: Tracked product sync failure', $event_data);
        }
    }
    
    /**
     * Track order sent
     *
     * @param int $order_id WooCommerce order ID
     * @param string $printify_order_id Printify order ID
     * @param array $response API response
     */
    public function track_order_sent($order_id, $printify_order_id, $response) {
        if (!$this->is_tracking_enabled()) {
            return;
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $event_data = array(
            'event_name' => 'order_sent',
            'event_category' => 'Printify',
            'event_label' => 'Order #' . $order->get_order_number(),
            'order_id' => $order_id,
            'printify_order_id' => $printify_order_id,
            'order_value' => $order->get_total(),
            'currency' => $order->get_currency(),
            'order_status' => $order->get_status()
        );
        
        $this->send_event_tracking($event_data);
        
        if ($this->debug) {
            Logger::get_instance()->debug('GA Helper: Tracked order sent', $event_data);
        }
    }
    
    /**
     * Track order status change
     *
     * @param int $order_id WooCommerce order ID
     * @param string $old_status Old status
     * @param string $new_status New status
     */
    public function track_order_status_change($order_id, $old_status, $new_status) {
        if (!$this->is_tracking_enabled()) {
            return;
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $printify_order_id = get_post_meta($order_id, '_printify_order_id', true);
        
        $event_data = array(
            'event_name' => 'order_status_change',
            'event_category' => 'Printify',
            'event_label' => 'Order #' . $order->get_order_number(),
            'order_id' => $order_id,
            'printify_order_id' => $printify_order_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'order_value' => $order->get_total(),
            'currency' => $order->get_currency()
        );
        
        $this->send_event_tracking($event_data);
        
        if ($this->debug) {
            Logger::get_instance()->debug('GA Helper: Tracked order status change', $event_data);
        }
    }
    
    /**
     * Track settings update
     */
    public function track_settings_update() {
        if (!$this->is_tracking_enabled()) {
            return;
        }
        
        $event_data = array(
            'event_name' => 'settings_update',
            'event_category' => 'Printify',
            'event_label' => 'Settings Updated',
            'user_id' => get_current_user_id()
        );
        
        $this->send_event_tracking($event_data);
        
        if ($this->debug) {
            Logger::get_instance()->debug('GA Helper: Tracked settings update', $event_data);
        }
    }
    
    /**
     * Track bulk product import
     *
     * @param array $product_ids WooCommerce product IDs
     * @param int $count Count of products imported
     */
    public function track_bulk_product_import($product_ids, $count) {
        if (!$this->is_tracking_enabled()) {
            return;
        }
        
        $event_data = array(
            'event_name' => 'bulk_product_import',
            'event_category' => 'Printify',
            'event_label' => 'Bulk Import',
            'product_count' => $count,
            'user_id' => get_current_user_id()
        );
        
        $this->send_event_tracking($event_data);
        
        if ($this->debug) {
            Logger::get_instance()->debug('GA Helper: Tracked bulk product import', $event_data);
        }
    }
    
    /**
     * Track bulk order sync
     *
     * @param array $order_ids WooCommerce order IDs
     * @param int $count Count of orders synced
     */
    public function track_bulk_order_sync($order_ids, $count) {
        if (!$this->is_tracking_enabled()) {
            return;
        }
        
        $event_data = array(
            'event_name' => 'bulk_order_sync',
            'event_category' => 'Printify',
            'event_label' => 'Bulk Order Sync',
            'order_count' => $count,
            'user_id' => get_current_user_id()
        );
        
        $this->send_event_tracking($event_data);
        
        if ($this->debug) {
            Logger::get_instance()->debug('GA Helper: Tracked bulk order sync', $event_data);
        }
    }
    
    /**
     * Check if tracking is enabled
     *
     * @return bool
     */
    public function is_tracking_enabled() {
        if (!isset($this->settings['ga_tracking_id']) || empty($this->settings['ga_tracking_id'])) {
            return false;
        }
        
        if (!isset($this->settings['ga_enabled']) || $this->settings['ga_enabled'] !== 'yes') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Send event tracking
     *
     * @param array $event_data Event data
     * @return bool Success status
     */
    public function send_event_tracking($event_data) {
        // Check required data
        if (empty($event_data['event_name']) || empty($event_data['event_category'])) {
            return false;
        }
        
        // Default values
        $event_data = wp_parse_args($event_data, array(
            'event_name' => '',
            'event_category' => 'Printify',
            'event_label' => '',
            'event_value' => null
        ));
        
        $tracking_id = $this->settings['ga_tracking_id'] ?? '';
        $is_ga4 = isset($this->settings['ga_version']) && $this->settings['ga_version'] === 'ga4';
        
        if (empty($tracking_id)) {
            return false;
        }
        
        // Different implementation based on GA version
        if ($is_ga4) {
            return $this->send_ga4_event($tracking_id, $event_data);
        } else {
            return $this->send_ua_event($tracking_id, $event_data);
        }
    }
    
    /**
     * Send GA4 event using Measurement Protocol
     *
     * @param string $tracking_id Tracking ID
     * @param array $event_data Event data
     * @return bool Success status
     */
    private function send_ga4_event($tracking_id, $event_data) {
        // GA4 uses a different endpoint and format
        $measurement_id = $tracking_id;
        $api_secret = $this->settings['ga4_api_secret'] ?? '';
        
        if (empty($api_secret)) {
            if ($this->debug) {
                Logger::get_instance()->debug('GA Helper: Missing GA4 API secret', array('measurement_id' => $measurement_id));
            }
            return false;
        }
        
        $endpoint = 'https://www.google-analytics.com/mp/collect';
        $client_id = $this->get_client_id();
        
        $params = array(
            'client_id' => $client_id,
            'non_personalized_ads' => true
        );
        
        $payload = array(
            'events' => array(
                array(
                    'name' => $event_data['event_name'],
                    'params' => array(
                        'engagement_time_msec' => 100,
                        'event_category' => $event_data['event_category'],
                        'event_label' => $event_data['event_label']
                    )
                )
            )
        );
        
        // Add additional parameters to the event
        foreach ($event_data as $key => $value) {
            if (!in_array($key, array('event_name', 'event_category', 'event_label', 'event_value'))) {
                $payload['events'][0]['params'][$key] = $value;
            }
        }
        
        // Add value if present
        if (!is_null($event_data['event_value'])) {
            $payload['events'][0]['params']['value'] = floatval