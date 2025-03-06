<?php
/**
 * Environment Settings
 *
 * Adds environment toggle and indicator for switching between Production/Development.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Settings
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class EnvironmentSettings {
    /**
     * Initialize the environment settings
     */
    public function init() {
        // Add environment settings section
        add_filter('wpwprintifysync_settings_sections', array($this, 'add_environment_section'), 5);
        
        // Add environment fields
        add_filter('wpwprintifysync_settings_fields', array($this, 'add_environment_fields'), 5);
        
        // Add admin notice for development mode
        add_action('admin_notices', array($this, 'maybe_show_dev_mode_notice'));
        
        // Add admin bar indicator
        add_action('admin_bar_menu', array($this, 'add_environment_indicator'), 100);
        
        // Hide Postman in production
        add_action('admin_menu', array($this, 'maybe_hide_postman_menu'), 999);
        
        // Add environment class to admin body
        add_filter('admin_body_class', array($this, 'add_environment_body_class'));
    }
    
    /**
     * Add environment settings section
     *
     * @param array $sections Settings sections
     * @return array Modified settings sections
     */
    public function add_environment_section($sections) {
        $sections['general']['environment'] = array(
            'title' => __('Environment Settings', 'wp-woocommerce-printify-sync'),
            'description' => __('Configure the system environment for development or production use.', 'wp-woocommerce-printify-sync'),
            'priority' => 5,
        );
        
        return $sections;
    }
    
    /**
     * Add environment settings fields
     *
     * @param array $fields Settings fields
     * @return array Modified settings fields
     */
    public function add_environment_fields($fields) {
        if (!isset($fields['general'])) {
            $fields['general'] = array();
        }
        
        if (!isset($fields['general']['environment'])) {
            $fields['general']['environment'] = array();
        }
        
        $fields['general']['environment'][] = array(
            'id' => 'environment_mode',
            'name' => __('Environment Mode', 'wp-woocommerce-printify-sync'),
            'type' => 'toggle',
            'options' => array(
                'production' => __('Production', 'wp-woocommerce-printify-sync'),
                'development' => __('Development', 'wp-woocommerce-printify-sync'),
            ),
            'default' => 'production',
            'description' => __('Set the environment mode. Development mode enables additional debugging features.', 'wp-woocommerce-printify-sync'),
            'css' => 'min-width:350px;',
        );
        
        $fields['general']['environment'][] = array(
            'id' => 'development_api_url',
            'name' => __('Development API URL', 'wp-woocommerce-printify-sync'),
            'type' => 'text',
            'default' => 'https://dev-api.printify.com/v1/',
            'description' => __('The API URL to use in development mode.', 'wp-woocommerce-printify-sync'),
            'placeholder' => 'https://dev-api.printify.com/v1/',
            'css' => 'min-width:350px;',
            'dependency' => array(
                'id' => 'environment_mode',
                'value' => 'development',
                'comparison' => '==',
            ),
        );
        
        $fields['general']['environment'][] = array(
            'id' => 'enable_debug_logging',
            'name' => __('Enable Debug Logging', 'wp-woocommerce-printify-sync'),
            'type' => 'checkbox',
            'default' => 'no',
            'description' => __('Enable detailed debug logging.', 'wp-woocommerce-printify-sync'),
            'dependency' => array(
                'id' => 'environment_mode',
                'value' => 'development',
                'comparison' => '==',
            ),
        );
        
        return $fields;
    }
    
    /**
     * Maybe show development mode notice
     */
    public function maybe_show_dev_mode_notice() {
        $settings = get_option('wpwprintifysync_settings', array());
        $environment_mode = isset($settings['environment_mode']) ? $settings['environment_mode'] : 'production';
        
        if ($environment_mode === 'development') {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('Printify Sync: Development Mode Active', 'wp-woocommerce-printify-sync'); ?></strong>
                    <?php esc_html_e('The plugin is currently running in development mode. Do not use in production.', 'wp-woocommerce-printify-sync'); ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Add environment indicator to admin bar
     *
     * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object
     */
    public function add_environment_indicator($wp_admin_bar) {
        $settings = get_option('wpwprintifysync_settings', array());
        $environment_mode = isset($settings['environment_mode']) ? $settings['environment_mode'] : 'production';
        
        $color = $environment_mode === 'development' ? '#FF5722' : '#4CAF50';
        $title = $environment_mode === 'development' ? __('DEV MODE', 'wp-woocommerce-printify-sync') : __('PRODUCTION', 'wp-woocommerce-printify-sync');
        
        $wp_admin_bar->add_node(array(
            'id' => 'wpwprintifysync-environment',
            'title' => sprintf(
                '<span style="background: %s; color: white; padding: 2px 8px; border-radius: 3px; font-weight: bold;">%s</span>',
                esc_attr($color),
                esc_html($title)
            ),
            'href' => admin_url('admin.php?page=wpwprintifysync-settings&tab=general'),
            'meta' => array(
                'title' => __('Click to change environment settings', 'wp-woocommerce-printify-sync'),
            ),
        ));
    }
    
    /**
     * Hide Postman menu in production
     */
    public function maybe_hide_postman_menu() {
        $settings = get_option('wpwprintifysync_settings', array());
        $environment_mode = isset($settings['environment_mode']) ? $settings['environment_mode'] : 'production';
        
        if ($environment_mode === 'production') {
            // Hide Postman in production mode
            remove_menu_page('postman');
        }
    }
    
    /**
     * Add environment class to admin body
     *
     * @param string $classes Admin body classes
     * @return string Modified admin body classes
     */
    public function add_environment_body_class($classes) {
        $settings = get_option('wpwprintifysync_settings', array());
        $environment_mode = isset($settings['environment_mode']) ? $settings['environment_mode'] : 'production';
        
        return $classes . ' wpwprintifysync-env-' . $environment_mode;
    }
}