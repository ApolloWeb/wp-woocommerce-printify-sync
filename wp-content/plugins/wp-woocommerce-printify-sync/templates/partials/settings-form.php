<?php defined('ABSPATH') || die('Direct access not allowed.'); ?>

<div class="card">
    <div class="card-body">
        <form id="wpwpsSettingsForm" class="needs-validation" novalidate>
            <input type="hidden" name="action" value="wpwps_save_settings">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpwps-nonce'); ?>">
            
            <div class="mb-3">
                <label for="endpoint" class="form-label"><?php esc_html_e('API Endpoint', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="url" class="form-control" id="endpoint" name="endpoint" value="<?php echo esc_attr(get_option('wpwps_api_endpoint')); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="api_key" class="form-label"><?php esc_html_e('API Key', 'wp-woocommerce-printify-sync'); ?></label>
                <input type="password" class="form-control" id="api_key" name="api_key" value="">
            </div>

            <button type="submit" class="btn btn-primary">
                <?php esc_html_e('Save Settings', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </form>
    </div>
</div>

<!-- Toast Container -->
<div id="wpwpsToasts" class="toast-container position-fixed top-0 end-0 p-3"></div>