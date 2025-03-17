<?php
/**
 * Admin Settings Template
 *
 * @var array $settings
 * @var array $apiStatus
 */
?>

<div class="wrap wpwps-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors(); ?>

    <div class="wpwps-settings-grid">
        <div class="wpwps-main-settings">
            <form method="post" action="options.php" class="wpwps-form">
                <?php settings_fields('wpwps_settings'); ?>

                <div class="wpwps-card">
                    <div class="card-header">
                        <h2><?php _e('API Configuration', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="card-body">
                        <!-- Printify API -->
                        <div class="form-group">
                            <label for="printify_api_key">
                                <?php _e('Printify API Key', 'wp-woocommerce-printify-sync'); ?>
                            </label>
                            <div class="api-key-input">
                                <input 
                                    type="password" 
                                    id="printify_api_key" 
                                    name="wpwps_printify_api_key"
                                    value="<?php echo esc_attr($settings['printify_api_key']); ?>"
                                    class="regular-text"
                                >
                                <button type="button" class="button test-api" data-api="printify">
                                    <?php _e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            </div>
                            <span class="api-status <?php echo $apiStatus['printify'] ? 'success' : 'error'; ?>">
                                <?php 
                                echo $apiStatus['printify'] 
                                    ? __('Connected', 'wp-woocommerce-printify-sync')
                                    : __('Not Connected', 'wp-woocommerce-printify-sync');
                                ?>
                            </span>
                        </div>

                        <!-- Geolocation API -->
                        <div class="form-group">
                            <label for="geolocation_api_key">
                                <?php _e('Geolocation API Key', 'wp-woocommerce-printify-sync'); ?>
                            </label>
                            <div class="api-key-input">
                                <input 
                                    type="password" 
                                    id="geolocation_api_key" 
                                    name="wpwps_geolocation_api_key"
                                    value="<?php echo esc_attr($settings['geolocation_api_key']); ?>"
                                    class="regular-text"
                                >
                                <button type="button" class="button test-api" data-api="geolocation">
                                    <?php _e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            </div>
                            <span class="api-status <?php echo $apiStatus['geolocation'] ? 'success' : 'error'; ?>">
                                <?php 
                                echo $apiStatus['geolocation'] 
                                    ? __('Connected', 'wp-woocommerce-printify-sync')
                                    : __('Not Connected', 'wp-woocommerce-printify-sync');
                                ?>
                            </span>
                        </div>

                        <!-- Currency API -->
                        <div class="form-group">
                            <label for="currency_api_key">
                                <?php _e('Currency API Key', 'wp-woocommerce-printify-sync'); ?>
                            </label>
                            <div class="api-key-input">
                                <input 
                                    type="password" 
                                    id="currency_api_key" 
                                    name="wpwps_currency_api_key"
                                    value="<?php echo esc_attr($settings['currency_api_key']); ?>"
                                    class="regular-text"
                                >
                                <button type="button" class="button test-api" data-api="currency">
                                    <?php _e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            </div>
                            <span class="api-status <?php echo $apiStatus['currency'] ? 'success' : 'error'; ?>">
                                <?php 
                                echo $apiStatus['currency'] 
                                    ? __('Connected', 'wp-woocommerce-printify-sync')
                                    : __('Not Connected', 'wp-woocommerce-printify-sync');
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="wpwps-card">
                    <div class="card-header">
                        <h2><?php _e('Storage Configuration', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="card-body">
                        <!-- Google Drive -->
                        <div class="form-group">
                            <label for="google_drive_credentials">
                                <?php _e('Google Drive Credentials', 'wp-woocommerce-printify-sync'); ?>
                            </label>
                            <textarea 
                                id="google_drive_credentials" 
                                name="wpwps_google_drive_credentials"
                                class="large-text code"
                                rows="5"
                            ><?php echo esc_textarea($settings['google_drive_credentials']); ?></textarea>
                        </div>

                        <!-- Cloudflare R2 -->
                        <div class="form-group">
                            <label for="cloudflare_r2_credentials">
                                <?php _e('Cloudflare R2 Credentials', 'wp-woocommerce-printify-sync'); ?>
                            </label>
                            <textarea 
                                id="cloudflare_r2_credentials" 
                                name="wpwps_cloudflare_r2_credentials"
                                class="large-text code"
                                rows="5"
                            ><?php echo esc_textarea($settings['cloudflare_r2_credentials']); ?></textarea>
                        </div>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <div class="wpwps-settings-sidebar">
            <div class="wpwps-card">
                <div class="card-header">
                    <h2><?php _e('Quick Actions', 'wp-woocommerce-printify-sync'); ?></h2>
                </div>
                <div class="card-body">
                    <button type="button" class="button button-primary test-all-apis">
                        <?php _e('Test All Connections', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                    <button type="button" class="button clear-api-cache">
                        <?php _e('Clear API Cache', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                </div>
            </div>

            <div class="wpwps-card">
                <div class="card-header">
                    <h2><?php _e('Documentation', 'wp-woocommerce-printify-sync'); ?></h2>
                </div>
                <div class="card-body">
                    <ul class="documentation-links">
                        <li>
                            <a href="https://printify.com/api" target="_blank">
                                <?php _e('Printify API Docs', 'wp-woocommerce-printify-sync'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="https://ipgeolocation.io/documentation" target="_blank">
                                <?php _e('Geolocation API Docs', 'wp-woocommerce-printify-sync'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="https://freecurrencyapi.com/docs" target="_blank">
                                <?php _e('Currency API Docs', 'wp-woocommerce-printify-sync'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>