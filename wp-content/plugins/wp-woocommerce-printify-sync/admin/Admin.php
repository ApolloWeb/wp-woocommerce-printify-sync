<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * The admin-specific functionality of the plugin.
 */
class Admin {

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_options_page'));
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'wps_options',
            'wps_upload_max_size',
            array(
                'type' => 'integer',
                'description' => 'Maximum upload size in MB',
                'sanitize_callback' => array($this, 'sanitize_upload_size'),
                'default' => 64,
            )
        );

        add_settings_section(
            'wps_upload_settings',
            __('Upload Settings', 'wp-woocommerce-printify-sync'),
            array($this, 'upload_settings_section_callback'),
            'wps_options'
        );

        add_settings_field(
            'wps_upload_max_size',
            __('Maximum Upload Size (MB)', 'wp-woocommerce-printify-sync'),
            array($this, 'upload_size_field_callback'),
            'wps_options',
            'wps_upload_settings'
        );
    }

    /**
     * Add options page
     */
    public function add_options_page() {
        add_options_page(
            __('Printify Sync Settings', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wps_options',
            array($this, 'render_options_page')
        );
    }

    /**
     * Render the options page
     */
    public function render_options_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wps_options');
                do_settings_sections('wps_options');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Upload settings section callback
     */
    public function upload_settings_section_callback() {
        echo '<p>' . esc_html__('Configure maximum upload size for Printify Sync files.', 'wp-woocommerce-printify-sync') . '</p>';
        // Display current PHP upload limits
        echo '<p>' . sprintf(
            __('Current PHP upload_max_filesize: %s', 'wp-woocommerce-printify-sync'),
            ini_get('upload_max_filesize')
        ) . '</p>';
        echo '<p>' . sprintf(
            __('Current PHP post_max_size: %s', 'wp-woocommerce-printify-sync'),
            ini_get('post_max_size')
        ) . '</p>';
    }

    /**
     * Upload size field callback
     */
    public function upload_size_field_callback() {
        $value = get_option('wps_upload_max_size', 64);
        echo '<input type="number" id="wps_upload_max_size" name="wps_upload_max_size" value="' . esc_attr($value) . '" min="1" max="2048" />';
        echo '<p class="description">' . esc_html__('Maximum file size in megabytes (MB). Note: This cannot exceed your server\'s PHP limits unless you modify php.ini settings.', 'wp-woocommerce-printify-sync') . '</p>';
    }

    /**
     * Sanitize upload size
     *
     * @param mixed $value The unsanitized value
     * @return int Sanitized value
     */
    public function sanitize_upload_size($value) {
        $value = absint($value);
        if ($value < 1) {
            $value = 1;
        } elseif ($value > 2048) {
            $value = 2048;
        }
        return $value;
    }
}