<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Core\Security;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\APIServiceInterface;

class Settings {
    // ...existing code...

    public function __construct(APIServiceInterface $api) {
        $this->api = $api;
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_wpwps_test_api', [$this, 'ajax_test_api']);
        add_action('wp_ajax_wpwps_get_shops', [$this, 'ajax_get_shops']);
        add_action('wp_ajax_wpwps_save_settings', [$this, 'ajax_save_settings']);
    }

    public function register_settings() {
        register_setting('wpwps_settings', 'wwps_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings']
        ]);
        // ...existing code...
    }

    public function sanitize_settings($value) {
        if (!empty($value['api_key'])) {
            $value['api_key'] = Security::encrypt($value['api_key']);
        }
        return $value;
    }

    public function ajax_get_shops() {
        check_ajax_referer('wpwps_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'wp-woocommerce-printify-sync')]);
        }

        $shops = $this->api->request('shops.json');
        
        if (is_wp_error($shops)) {
            wp_send_json_error(['message' => $shops->get_error_message()]);
        }

        wp_send_json_success(['shops' => $shops]);
    }

    public function ajax_save_settings() {
        check_ajax_referer('wpwps_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'wp-woocommerce-printify-sync')]);
        }

        $settings = array_map('sanitize_text_field', $_POST['settings'] ?? []);
        update_option('wwps_settings', $settings);

        wp_send_json_success(['message' => __('Settings saved successfully!', 'wp-woocommerce-printify-sync')]);
    }

    public function render_settings_page() {
        $options = get_option('wwps_settings', []);
        $has_shop = !empty($options['shop_id']);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <i class="fas fa-cog mr-2"></i>
                <?php _e('Printify Settings', 'wp-woocommerce-printify-sync'); ?>
            </h1>
            
            <div class="row mt-4">
                <div class="col-md-8 col-lg-6">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-key mr-2"></i>
                                <?php _e('API Configuration', 'wp-woocommerce-printify-sync'); ?>
                            </h3>
                        </div>
                        <div class="card-body wpwps-settings-wrapper">
                            <form id="wpwps-settings-form" method="post">
                                <div class="form-group">
                                    <label for="wpwps_settings_api_key">
                                        <?php _e('API Key', 'wp-woocommerce-printify-sync'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control"
                                               id="wpwps_settings_api_key"
                                               name="settings[api_key]" 
                                               value="<?php echo $has_shop ? '••••••••' : ''; ?>"
                                               <?php echo $has_shop ? 'disabled' : ''; ?>>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="toggle-api-key">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group" id="shop-selector" style="display: none;">
                                    <label for="wpwps_settings_shop_id">
                                        <?php _e('Select Shop', 'wp-woocommerce-printify-sync'); ?>
                                    </label>
                                    <select class="form-control" 
                                            id="wpwps_settings_shop_id"
                                            name="settings[shop_id]"
                                            <?php echo $has_shop ? 'disabled' : ''; ?>>
                                    </select>
                                </div>

                                <div class="wpwps-api-test mb-3">
                                    <button type="button" id="wpwps-test-api" class="btn btn-info">
                                        <i class="fas fa-plug mr-1"></i>
                                        <?php _e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                    <div id="wpwps-api-test-result" class="mt-2"></div>
                                </div>

                                <?php if (!$has_shop): ?>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-1"></i>
                                        <?php _e('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
