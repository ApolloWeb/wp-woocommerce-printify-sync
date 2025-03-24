<?php
/**
 * Settings class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Settings class.
 */
class Settings {
    /**
     * The option name.
     *
     * @var string
     */
    private $option_name = 'wpwps_settings';

    /**
     * The default options.
     *
     * @var array
     */
    private $default_options = [
        'api_key' => '',
        'auto_sync' => false,
        'sync_interval' => 'daily',
        'product_status' => 'draft',
        'import_images' => true,
        'log_enabled' => true,
    ];

    /**
     * Constructor.
     */
    public function __construct() {
        // Initialize options
    }

    /**
     * Initialize the settings.
     *
     * @return void
     */
    public function init() {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register settings.
     *
     * @return void
     */
    public function registerSettings() {
        register_setting(
            'wpwps_settings_group',
            $this->option_name,
            [
                'sanitize_callback' => [$this, 'sanitizeOptions'],
            ]
        );

        add_settings_section(
            'wpwps_api_settings',
            __('API Settings', 'wp-woocommerce-printify-sync'),
            [$this, 'apiSettingsSectionCallback'],
            'wpwps_settings'
        );

        add_settings_field(
            'api_key',
            __('Printify API Key', 'wp-woocommerce-printify-sync'),
            [$this, 'apiKeyFieldCallback'],
            'wpwps_settings',
            'wpwps_api_settings'
        );

        add_settings_section(
            'wpwps_sync_settings',
            __('Sync Settings', 'wp-woocommerce-printify-sync'),
            [$this, 'syncSettingsSectionCallback'],
            'wpwps_settings'
        );

        add_settings_field(
            'auto_sync',
            __('Auto Sync', 'wp-woocommerce-printify-sync'),
            [$this, 'autoSyncFieldCallback'],
            'wpwps_settings',
            'wpwps_sync_settings'
        );

        add_settings_field(
            'sync_interval',
            __('Sync Interval', 'wp-woocommerce-printify-sync'),
            [$this, 'syncIntervalFieldCallback'],
            'wpwps_settings',
            'wpwps_sync_settings'
        );

        add_settings_field(
            'product_status',
            __('Product Status', 'wp-woocommerce-printify-sync'),
            [$this, 'productStatusFieldCallback'],
            'wpwps_settings',
            'wpwps_sync_settings'
        );

        add_settings_field(
            'import_images',
            __('Import Images', 'wp-woocommerce-printify-sync'),
            [$this, 'importImagesFieldCallback'],
            'wpwps_settings',
            'wpwps_sync_settings'
        );

        add_settings_section(
            'wpwps_advanced_settings',
            __('Advanced Settings', 'wp-woocommerce-printify-sync'),
            [$this, 'advancedSettingsSectionCallback'],
            'wpwps_settings'
        );

        add_settings_field(
            'log_enabled',
            __('Enable Logging', 'wp-woocommerce-printify-sync'),
            [$this, 'logEnabledFieldCallback'],
            'wpwps_settings',
            'wpwps_advanced_settings'
        );
    }

    /**
     * Sanitize options.
     *
     * @param array $input The options to sanitize.
     * @return array
     */
    public function sanitizeOptions($input) {
        $sanitized = [];
        
        // API Key
        $sanitized['api_key'] = sanitize_text_field($input['api_key'] ?? '');
        
        // Auto Sync
        $sanitized['auto_sync'] = isset($input['auto_sync']) && $input['auto_sync'] ? true : false;
        
        // Sync Interval
        $sanitized['sync_interval'] = isset($input['sync_interval']) && in_array($input['sync_interval'], ['hourly', 'twicedaily', 'daily'], true) 
            ? $input['sync_interval'] 
            : 'daily';
        
        // Product Status
        $sanitized['product_status'] = isset($input['product_status']) && in_array($input['product_status'], ['draft', 'publish', 'pending'], true) 
            ? $input['product_status'] 
            : 'draft';
        
        // Import Images
        $sanitized['import_images'] = isset($input['import_images']) && $input['import_images'] ? true : false;
        
        // Log Enabled
        $sanitized['log_enabled'] = isset($input['log_enabled']) && $input['log_enabled'] ? true : false;
        
        return $sanitized;
    }

    /**
     * API settings section callback.
     *
     * @return void
     */
    public function apiSettingsSectionCallback() {
        echo '<p>' . esc_html__('Configure your Printify API settings here.', 'wp-woocommerce-printify-sync') . '</p>';
    }

    /**
     * API key field callback.
     *
     * @return void
     */
    public function apiKeyFieldCallback() {
        $options = $this->getOptions();
        echo '<input type="text" id="api_key" name="' . esc_attr($this->option_name) . '[api_key]" value="' . esc_attr($options['api_key']) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Enter your Printify API key. You can find this in your Printify account settings.', 'wp-woocommerce-printify-sync') . '</p>';
        echo '<button type="button" id="wpwps-test-connection" class="button button-secondary">' . esc_html__('Test Connection', 'wp-woocommerce-printify-sync') . '</button>';
        echo '<span id="wpwps-connection-status" style="margin-left: 10px;"></span>';
    }

    /**
     * Sync settings section callback.
     *
     * @return void
     */
    public function syncSettingsSectionCallback() {
        echo '<p>' . esc_html__('Configure how products are synced from Printify to WooCommerce.', 'wp-woocommerce-printify-sync') . '</p>';
    }

    /**
     * Auto sync field callback.
     *
     * @return void
     */
    public function autoSyncFieldCallback() {
        $options = $this->getOptions();
        echo '<input type="checkbox" id="auto_sync" name="' . esc_attr($this->option_name) . '[auto_sync]" value="1" ' . checked($options['auto_sync'], true, false) . ' />';
        echo '<p class="description">' . esc_html__('Enable automatic syncing of products from Printify.', 'wp-woocommerce-printify-sync') . '</p>';
    }

    /**
     * Sync interval field callback.
     *
     * @return void
     */
    public function syncIntervalFieldCallback() {
        $options = $this->getOptions();
        echo '<select id="sync_interval" name="' . esc_attr($this->option_name) . '[sync_interval]">';
        echo '<option value="hourly" ' . selected($options['sync_interval'], 'hourly', false) . '>' . esc_html__('Hourly', 'wp-woocommerce-printify-sync') . '</option>';
        echo '<option value="twicedaily" ' . selected($options['sync_interval'], 'twicedaily', false) . '>' . esc_html__('Twice Daily', 'wp-woocommerce-printify-sync') . '</option>';
        echo '<option value="daily" ' . selected($options['sync_interval'], 'daily', false) . '>' . esc_html__('Daily', 'wp-woocommerce-printify-sync') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('How often to sync products from Printify.', 'wp-woocommerce-printify-sync') . '</p>';
    }

    /**
     * Product status field callback.
     *
     * @return void
     */
    public function productStatusFieldCallback() {
        $options = $this->getOptions();
        echo '<select id="product_status" name="' . esc_attr($this->option_name) . '[product_status]">';
        echo '<option value="draft" ' . selected($options['product_status'], 'draft', false) . '>' . esc_html__('Draft', 'wp-woocommerce-printify-sync') . '</option>';
        echo '<option value="publish" ' . selected($options['product_status'], 'publish', false) . '>' . esc_html__('Published', 'wp-woocommerce-printify-sync') . '</option>';
        echo '<option value="pending" ' . selected($options['product_status'], 'pending', false) . '>' . esc_html__('Pending Review', 'wp-woocommerce-printify-sync') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('The status to set for newly imported products.', 'wp-woocommerce-printify-sync') . '</p>';
    }

    /**
     * Import images field callback.
     *
     * @return void
     */
    public function importImagesFieldCallback() {
        $options = $this->getOptions();
        echo '<input type="checkbox" id="import_images" name="' . esc_attr($this->option_name) . '[import_images]" value="1" ' . checked($options['import_images'], true, false) . ' />';
        echo '<p class="description">' . esc_html__('Import product images from Printify.', 'wp-woocommerce-printify-sync') . '</p>';
    }

    /**
     * Advanced settings section callback.
     *
     * @return void
     */
    public function advancedSettingsSectionCallback() {
        echo '<p>' . esc_html__('Advanced settings for the plugin.', 'wp-woocommerce-printify-sync') . '</p>';
    }

    /**
     * Log enabled field callback.
     *
     * @return void
     */
    public function logEnabledFieldCallback() {
        $options = $this->getOptions();
        echo '<input type="checkbox" id="log_enabled" name="' . esc_attr($this->option_name) . '[log_enabled]" value="1" ' . checked($options['log_enabled'], true, false) . ' />';
        echo '<p class="description">' . esc_html__('Enable logging of sync operations.', 'wp-woocommerce-printify-sync') . '</p>';
    }

    /**
     * Render the settings page.
     *
     * @return void
     */
    public function renderPage() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('WC Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wpwps_settings_group');
                do_settings_sections('wpwps_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get the options.
     *
     * @return array
     */
    public function getOptions() {
        $options = get_option($this->option_name, []);
        return wp_parse_args($options, $this->default_options);
    }

    /**
     * Get a specific option.
     *
     * @param string $key     The option key.
     * @param mixed  $default The default value.
     * @return mixed
     */
    public function getOption($key, $default = null) {
        $options = $this->getOptions();
        
        if (isset($options[$key])) {
            return $options[$key];
        }
        
        if (null !== $default) {
            return $default;
        }
        
        if (isset($this->default_options[$key])) {
            return $this->default_options[$key];
        }
        
        return null;
    }

    /**
     * Add default options.
     *
     * @return void
     */
    public function addDefaultOptions() {
        // Only add default options if they don't exist
        if (false === get_option($this->option_name)) {
            add_option($this->option_name, $this->default_options);
        }
    }
}
