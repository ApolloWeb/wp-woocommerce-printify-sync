<?php
/**
 * Settings Manager
 *
 * Handles settings rendering and saving.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SettingsManager {
    /**
     * Singleton instance
     *
     * @var SettingsManager
     */
    private static $instance = null;
    
    /**
     * Settings tabs
     *
     * @var array
     */
    private $tabs = array();
    
    /**
     * Current tab
     *
     * @var string
     */
    private $current_tab = '';
    
    /**
     * Settings sections
     *
     * @var array
     */
    private $sections = array();
    
    /**
     * Settings fields
     *
     * @var array
     */
    private $fields = array();
    
    /**
     * Get singleton instance
     *
     * @return SettingsManager
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
        $this->tabs = array(
            'general' => __('General', 'wp-woocommerce-printify-sync'),
            'products' => __('Products', 'wp-woocommerce-printify-sync'),
            'orders' => __('Orders', 'wp-woocommerce-printify-sync'),
            'currency' => __('Currency', 'wp-woocommerce-printify-sync'),
            'webhooks' => __('Webhooks', 'wp-woocommerce-printify-sync'),
            'analytics' => __('Analytics', 'wp-woocommerce-printify-sync'),
            'logs' => __('Logs', 'wp-woocommerce-printify-sync'),
            'advanced' => __('Advanced', 'wp-woocommerce-printify-sync')
        );
        
        // Apply filter for tabs
        $this->tabs = apply_filters('wpwprintifysync_settings_tabs', $this->tabs);
        
        // Set current tab
        $this->current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        if (!array_key_exists($this->current_tab, $this->tabs)) {
            $this->current_tab = 'general';
        }
        
        // Set sections
        $this->sections = $this->get_sections();
        
        // Set fields
        $this->fields = $this->get_fields();
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Add settings page
        add_action('admin_menu', array($this, 'add_settings_page'), 20);
    }
    
    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'wpwprintifysync',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwprintifysync-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Get settings sections
     *
     * @return array
     */
    public function get_sections() {
        $sections = array(
            'general' => array(
                'api' => array(
                    'title' => __('API Connection', 'wp-woocommerce-printify-sync'),
                    'description' => __('Configure Printify API connection settings.', 'wp-woocommerce-printify-sync')
                ),
                'shop' => array(
                    'title' => __('Shop Settings', 'wp-woocommerce-printify-sync'),
                    'description' => __('Configure shop-related settings.', 'wp-woocommerce-printify-sync')
                )
            ),
            'products' => array(
                'sync' => array(
                    'title' => __('Product Sync Settings', 'wp-woocommerce-printify-sync'),
                    'description' => __('Configure product synchronization settings.', 'wp-woocommerce-printify-sync')
                ),
                'import' => array(
                    'title' => __('Import Settings', 'wp-woocommerce-printify-sync'),
                    'description' => __('Configure product import settings.', 'wp-woocommerce-printify-sync')
                )
            ),
            'orders' => array(
                'sync' => array(
                    'title' => __('Order Sync Settings', 'wp-woocommerce-printify-sync'),
                    'description' => __('Configure order synchronization settings.', 'wp-woocommerce-printify-sync')
                ),
                'status' => array(
                    'title' => __('Status Mapping', 'wp-woocommerce-printify-sync'),
                    'description' => __('Map WooCommerce order statuses to Printify statuses.', 'wp-woocommerce-printify-sync')
                )
            ),
            'currency' => array(
                'settings' => array(
                    'title' => __('Currency Settings', 'wp-woocommerce-printify-sync'),
                    'description' => __('Configure multi-currency settings.', 'wp-woocommerce-printify-sync')
                )
            ),
            'webhooks' => array(
                'settings' => array(
                    'title' => __('Webhook Settings', 'wp-woocommerce-printify-sync'),
                    'description' => __('Configure webhook settings for real-time updates.', 'wp-woocommerce-printify-sync')
                )
            ),
            'analytics' => array(
                'settings' => array(
                    'title' => __('Analytics Settings', 'wp-woocommerce-printify-sync'),
                    'description' => __('Configure Google Analytics and tracking settings.', 'wp-woocommerce-printify-sync')
                )
            ),
            'logs' => array(
                'settings' => array(
                    'title' => __('Logging Settings', 'wp-woocommerce-printify-sync'),
                    'description' => __('Configure logging options.', 'wp-woocommerce-printify-sync')
                )
            ),
            'advanced' => array(
                'performance' => array(
                    'title' => __('Performance Settings', 'wp-woocommerce-printify-sync'),
                    'description' => __('Configure advanced performance options.', 'wp-woocommerce-printify-sync')
                ),
                'troubleshooting' => array(
                    'title' => __('Troubleshooting', 'wp-woocommerce-printify-sync'),
                    'description' => __('Tools for troubleshooting plugin issues.', 'wp-woocommerce-printify-sync')
                )
            )
        );
        
        return apply_filters('wpwprintifysync_settings_sections', $sections);
    }
    
    /**
     * Get settings fields
     *
     * @return array
     */
    public function get_fields() {
        $fields = array(
            'general' => array(
                'api' => array(
                    array(
                        'id' => 'api_key',
                        'name' => __('Printify API Key', 'wp-woocommerce-printify-sync'),
                        'type' => 'password',
                        'default' => '',
                        'placeholder' => __('Enter your Printify API key', 'wp-woocommerce-printify-sync'),
                        'description' => __('You can find your API key in your Printify account under Settings → API.', 'wp-woocommerce-printify-sync'),
                        'required' => true
                    ),
                    array(
                        'id' => 'shop_id',
                        'name' => __('Printify Shop ID', 'wp-woocommerce-printify-sync'),
                        'type' => 'select',
                        'options' => array(
                            '' => __('Select a shop', 'wp-woocommerce-printify-sync')
                        ),
                        'default' => '',
                        'description' => __('Select your Printify shop to connect with.', 'wp-woocommerce-printify-sync'),
                        'required' => true,
                        'dynamic_options' => true,
                        'callback' => array($this, 'get_shops_options')
                    ),
                    array(
                        'id' => 'test_connection',
                        'name' => __('Test Connection', 'wp-woocommerce-printify-sync'),
                        'type' => 'button',
                        'value' => __('Test Connection', 'wp-woocommerce-printify-sync'),
                        'description' => __('Click to test the connection to Printify API.', 'wp-woocommerce-printify-sync'),
                        'callback' => 'test_printify_connection'
                    )
                ),
                'shop' => array(
                    array(
                        'id' => 'store_name',
                        'name' => __('Store Name', 'wp-woocommerce-printify-sync'),
                        'type' => 'text',
                        'default' => get_bloginfo('name'),
                        'description' => __('Your store name to use in Printify.', 'wp-woocommerce-printify-sync')
                    ),
                    array(
                        'id' => 'default_currency',
                        'name' => __('Default Currency', 'wp-woocommerce-printify-sync'),
                        'type' => 'select',
                        'options' => array(
                            'USD' => __('US Dollar (USD)', 'wp-woocommerce-printify-sync'),
                            'EUR' => __('Euro (EUR)', 'wp-woocommerce-printify-sync'),
                            'GBP' => __('British Pound (GBP)', 'wp-woocommerce-printify-sync'),
                            'CAD' => __('Canadian Dollar (CAD)', 'wp-woocommerce-printify-sync'),
                            'AUD' => __('Australian Dollar (AUD)', 'wp-woocommerce-printify-sync')
                        ),
                        'default' => 'USD',
                        'description' => __('Default currency for your store.', 'wp-woocommerce-printify-sync')
                    )
                )
            ),
            // Additional fields for other tabs would be defined here
        );
        
        return apply_filters('wpwprintifysync_settings_fields', $fields);
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'wpwprintifysync_settings',
            'wpwprintifysync_settings',
            array($this, 'sanitize_settings')
        );
        
        // Register sections and fields
        foreach ($this->sections as $tab => $sections) {
            foreach ($sections as $section_id => $section) {
                add_settings_