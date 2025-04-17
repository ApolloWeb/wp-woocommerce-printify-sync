<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Class to handle plugin settings
 */
class Settings {
    /**
     * Option group
     *
     * @var string
     */
    private $option_group = 'wpwps_settings';

    /**
     * Option name
     *
     * @var string
     */
    private $option_name = 'wpwps_options';

    /**
     * Settings sections
     *
     * @var array
     */
    private $sections = [];

    /**
     * Settings fields
     *
     * @var array
     */
    private $fields = [];

    /**
     * Initialize the class
     */
    public function init() {
        add_action('admin_init', [$this, 'register_settings']);
        $this->setup_fields();
    }
    
    /**
     * Set up settings fields and sections
     */
    private function setup_fields() {
        // Define settings sections
        $this->sections = [
            'general' => [
                'id'    => 'general',
                'title' => __('General Settings', 'wp-woocommerce-printify-sync'),
                'description' => __('Configure general plugin settings', 'wp-woocommerce-printify-sync'),
                'order' => 10,
            ],
            'api' => [
                'id'    => 'api',
                'title' => __('API Settings', 'wp-woocommerce-printify-sync'),
                'description' => __('Configure Printify API connection', 'wp-woocommerce-printify-sync'),
                'order' => 20,
            ],
            'sync' => [
                'id'    => 'sync',
                'title' => __('Sync Settings', 'wp-woocommerce-printify-sync'),
                'description' => __('Configure product synchronization settings', 'wp-woocommerce-printify-sync'),
                'order' => 30,
            ],
        ];
        
        // Define settings fields
        $this->fields = [
            'general' => [
                [
                    'id'          => 'enable_sync',
                    'title'       => __('Enable Sync', 'wp-woocommerce-printify-sync'),
                    'description' => __('Enable automatic product synchronization', 'wp-woocommerce-printify-sync'),
                    'type'        => 'checkbox',
                    'checkbox_label' => __('Enable', 'wp-woocommerce-printify-sync'),
                    'default'     => 1,
                ],
                [
                    'id'          => 'log_level',
                    'title'       => __('Log Level', 'wp-woocommerce-printify-sync'),
                    'description' => __('Select the level of detail for logs', 'wp-woocommerce-printify-sync'),
                    'type'        => 'select',
                    'options'     => [
                        'none' => __('None', 'wp-woocommerce-printify-sync'),
                        'error' => __('Errors Only', 'wp-woocommerce-printify-sync'),
                        'warning' => __('Warnings & Errors', 'wp-woocommerce-printify-sync'),
                        'info' => __('Info, Warnings & Errors', 'wp-woocommerce-printify-sync'),
                        'debug' => __('All (Debug)', 'wp-woocommerce-printify-sync'),
                    ],
                    'default'     => 'error',
                ],
            ],
            'api' => [
                [
                    'id'          => 'api_key',
                    'title'       => __('Printify API Key', 'wp-woocommerce-printify-sync'),
                    'description' => __('Enter your Printify API key', 'wp-woocommerce-printify-sync'),
                    'type'        => 'text',
                    'default'     => '',
                ],
                [
                    'id'          => 'shop_id',
                    'title'       => __('Shop ID', 'wp-woocommerce-printify-sync'),
                    'description' => __('Enter your Printify Shop ID', 'wp-woocommerce-printify-sync'),
                    'type'        => 'text',
                    'default'     => '',
                ],
            ],
            'sync' => [
                [
                    'id'          => 'sync_interval',
                    'title'       => __('Sync Interval', 'wp-woocommerce-printify-sync'),
                    'description' => __('How often to check for updates (in minutes)', 'wp-woocommerce-printify-sync'),
                    'type'        => 'number',
                    'default'     => 60,
                ],
                [
                    'id'          => 'webhook_enabled',
                    'title'       => __('Enable Webhooks', 'wp-woocommerce-printify-sync'),
                    'description' => __('Use webhooks for real-time updates instead of scheduled checks', 'wp-woocommerce-printify-sync'),
                    'type'        => 'checkbox',
                    'checkbox_label' => __('Enable', 'wp-woocommerce-printify-sync'),
                    'default'     => 0,
                ],
            ],
        ];
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            $this->option_group,
            $this->option_name,
            [$this, 'sanitize_options']
        );

        // Add sections and fields
        foreach ($this->sections as $section_id => $section) {
            add_settings_section(
                $section_id,
                $section['title'],
                [$this, 'section_callback'],
                $this->option_group
            );

            if (isset($this->fields[$section_id])) {
                foreach ($this->fields[$section_id] as $field) {
                    add_settings_field(
                        $field['id'],
                        $field['title'],
                        [$this, 'field_callback'],
                        $this->option_group,
                        $section_id,
                        $field
                    );
                }
            }
        }
    }

    /**
     * Section callback
     *
     * @param array $args
     */
    public function section_callback($args) {
        $section_id = $args['id'];
        if (isset($this->sections[$section_id]['description'])) {
            echo '<p>' . esc_html($this->sections[$section_id]['description']) . '</p>';
        }
    }

    /**
     * Field callback
     *
     * @param array $args
     */
    public function field_callback($args) {
        // This is handled by the bootstrap-styled custom field renderer
    }

    /**
     * Sanitize options
     *
     * @param array $options
     * @return array
     */
    public function sanitize_options($options) {
        if (!is_array($options)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($this->fields as $section_fields) {
            foreach ($section_fields as $field) {
                $id = $field['id'];
                
                // Skip if option is not set
                if (!isset($options[$id])) {
                    // For checkboxes, set to 0 when not checked
                    if ($field['type'] === 'checkbox') {
                        $sanitized[$id] = 0;
                    } elseif (isset($field['default'])) {
                        // Use default if available
                        $sanitized[$id] = $field['default'];
                    }
                    continue;
                }
                
                switch ($field['type']) {
                    case 'text':
                        $sanitized[$id] = sanitize_text_field($options[$id]);
                        break;
                        
                    case 'number':
                        $sanitized[$id] = absint($options[$id]);
                        break;
                        
                    case 'checkbox':
                        $sanitized[$id] = isset($options[$id]) ? 1 : 0;
                        break;
                        
                    case 'select':
                        if (isset($field['options']) && array_key_exists($options[$id], $field['options'])) {
                            $sanitized[$id] = $options[$id];
                        } elseif (isset($field['default'])) {
                            $sanitized[$id] = $field['default'];
                        }
                        break;
                        
                    case 'textarea':
                        $sanitized[$id] = sanitize_textarea_field($options[$id]);
                        break;
                        
                    default:
                        $sanitized[$id] = apply_filters('wpwps_sanitize_option_' . $field['type'], $options[$id], $field);
                        break;
                }
            }
        }
        
        return $sanitized;
    }

    /**
     * Get option
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get_option($key, $default = '') {
        $options = get_option($this->option_name, []);
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Get all sections
     * 
     * @return array
     */
    public function get_sections() {
        return $this->sections;
    }
    
    /**
     * Get all fields
     * 
     * @return array
     */
    public function get_fields() {
        return $this->fields;
    }
}
