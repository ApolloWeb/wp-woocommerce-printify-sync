<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * API Rate Limit Settings
 */
class RateLimitSettings {
    /**
     * Register settings
     */
    public function register_settings() {
        add_settings_section(
            'wpwps_rate_limit_section',
            __('API Rate Limiting', 'wp-woocommerce-printify-sync'),
            [$this, 'render_section_info'],
            'wpwps_advanced_settings'
        );
        
        // Enable rate limiting
        register_setting('wpwps_advanced_settings', 'wpwps_enable_rate_limiting', 'sanitize_text_field');
        add_settings_field(
            'wpwps_enable_rate_limiting',
            __('Enable API Rate Limiting', 'wp-woocommerce-printify-sync'),
            [$this, 'render_enable_field'],
            'wpwps_advanced_settings',
            'wpwps_rate_limit_section'
        );
        
        // Default rate limit
        register_setting('wpwps_advanced_settings', 'wpwps_default_rate_limit', 'intval');
        add_settings_field(
            'wpwps_default_rate_limit',
            __('Default Rate Limit', 'wp-woocommerce-printify-sync'),
            [$this, 'render_default_rate_limit_field'],
            'wpwps_advanced_settings',
            'wpwps_rate_limit_section'
        );
        
        // Products rate limit
        register_setting('wpwps_advanced_settings', 'wpwps_products_rate_limit', 'intval');
        add_settings_field(
            'wpwps_products_rate_limit',
            __('Products Endpoint Rate Limit', 'wp-woocommerce-printify-sync'),
            [$this, 'render_products_rate_limit_field'],
            'wpwps_advanced_settings',
            'wpwps_rate_limit_section'
        );
        
        // Max retries
        register_setting('wpwps_advanced_settings', 'wpwps_max_retries', 'intval');
        add_settings_field(
            'wpwps_max_retries',
            __('Maximum Retries', 'wp-woocommerce-printify-sync'),
            [$this, 'render_max_retries_field'],
            'wpwps_advanced_settings',
            'wpwps_rate_limit_section'
        );
        
        // Base delay
        register_setting('wpwps_advanced_settings', 'wpwps_base_delay', 'intval');
        add_settings_field(
            'wpwps_base_delay',
            __('Base Retry Delay (seconds)', 'wp-woocommerce-printify-sync'),
            [$this, 'render_base_delay_field'],
            'wpwps_advanced_settings',
            'wpwps_rate_limit_section'
        );
    }
    
    /**
     * Render section information
     */
    public function render_section_info() {
        echo '<p>' . esc_html__('Configure API rate limiting to prevent hitting Printify\'s API limits and enable smart retries for failed requests.', 'wp-woocommerce-printify-sync') . '</p>';
    }
    
    /**
     * Render enable field
     */
    public function render_enable_field() {
        $value = get_option('wpwps_enable_rate_limiting', 'yes');
        ?>
        <label>
            <input type="checkbox" name="wpwps_enable_rate_limiting" value="yes" <?php checked($value, 'yes'); ?> />
            <?php esc_html_e('Enable rate limiting and retry mechanism', 'wp-woocommerce-printify-sync'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Automatically manage API request rates and retry failed requests with exponential backoff.', 'wp-woocommerce-printify-sync'); ?>
        </p>
        <?php
    }
    
    /**
     * Render default rate limit field
     */
    public function render_default_rate_limit_field() {
        $value = get_option('wpwps_default_rate_limit', 60);
        ?>
        <input type="number" name="wpwps_default_rate_limit" value="<?php echo esc_attr($value); ?>" min="1" max="100" class="small-text" />
        <p class="description">
            <?php esc_html_e('Maximum requests per minute for general API endpoints (default: 60)', 'wp-woocommerce-printify-sync'); ?>
        </p>
        <?php
    }
    
    /**
     * Render products rate limit field
     */
    public function render_products_rate_limit_field() {
        $value = get_option('wpwps_products_rate_limit', 30);
        ?>
        <input type="number" name="wpwps_products_rate_limit" value="<?php echo esc_attr($value); ?>" min="1" max="100" class="small-text" />
        <p class="description">
            <?php esc_html_e('Maximum requests per minute for product-related endpoints (default: 30)', 'wp-woocommerce-printify-sync'); ?>
        </p>
        <?php
    }
    
    /**
     * Render max retries field
     */
    public function render_max_retries_field() {
        $value = get_option('wpwps_max_retries', 5);
        ?>
        <input type="number" name="wpwps_max_retries" value="<?php echo esc_attr($value); ?>" min="1" max="10" class="small-text" />
        <p class="description">
            <?php esc_html_e('Maximum number of retry attempts for failed API requests (default: 5)', 'wp-woocommerce-printify-sync'); ?>
        </p>
        <?php
    }
    
    /**
     * Render base delay field
     */
    public function render_base_delay_field() {
        $value = get_option('wpwps_base_delay', 2);
        ?>
        <input type="number" name="wpwps_base_delay" value="<?php echo esc_attr($value); ?>" min="1" max="60" class="small-text" />
        <p class="description">
            <?php esc_html_e('Base delay in seconds for retry with exponential backoff (default: 2)', 'wp-woocommerce-printify-sync'); ?>
        </p>
        <?php
    }
}
