<?php
/**
 * POP3 Settings
 *
 * Manages POP3 settings registration and retrieval.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Email
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Pop3Settings {
    /**
     * Singleton instance
     *
     * @var Pop3Settings
     */
    private static $instance = null;
    
    /**
     * Settings array
     *
     * @var array
     */
    private $settings = array();
    
    /**
     * Get singleton instance
     *
     * @return Pop3Settings
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
        $this->load_settings();
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Load settings from WordPress options
     */
    private function load_settings() {
        $plugin_settings = get_option('wpwprintifysync_settings', array());
        
        $this->settings = array(
            'host' => isset($plugin_settings['pop3_host']) ? $plugin_settings['pop3_host'] : '',
            'port' => isset($plugin_settings['pop3_port']) ? intval($plugin_settings['pop3_port']) : 110,
            'username' => isset($plugin_settings['pop3_username']) ? $plugin_settings['pop3_username'] : '',
            'password' => isset($plugin_settings['pop3_password']) ? $plugin_settings['pop3_password'] : '',
            'ssl' => isset($plugin_settings['pop3_ssl']) && $plugin_settings['pop3_ssl'] === 'yes',
            'enabled' => isset($plugin_settings['pop3_enabled']) && $plugin_settings['pop3_enabled'] === 'yes',
            'delete_after_processing' => isset($plugin_settings['pop3_delete_after']) ? $plugin_settings['pop3_delete_after'] === 'yes' : true, // Default to true
            'auto_process' => isset($plugin_settings['pop3_auto_process']) && $plugin_settings['pop3_auto_process'] === 'yes',
            'auto_process_interval' => isset($plugin_settings['pop3_process_interval']) ? intval($plugin_settings['pop3_process_interval']) : 30,
            'max_emails_per_batch' => isset($plugin_settings['pop3_max_emails']) ? intval($plugin_settings['pop3_max_emails']) : 50,
            'from_filter' => isset($plugin_settings['pop3_from_filter']) ? $plugin_settings['pop3_from_filter'] : 'notifications@printify.com',
            'subject_filter' => isset($plugin_settings['