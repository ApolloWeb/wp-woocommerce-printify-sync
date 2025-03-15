<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-wrapper">
    <h1><?php echo esc_html__('Settings', 'wp-woocommerce-printify-sync'); ?></h1>

    <div class="wpwps-settings-container">
        <!-- Printify API Settings -->
        <div class="wpwps-card" id="printify-settings">
            <div class="wpwps-card-header">
                <h2><?php echo esc_html__('Printify API Settings', 'wp-woocommerce-printify-sync'); ?></h2>
            </div>
            <div class="wpwps-card-body">
                <form class="wpwps-settings-form" data-section="printify">
                    <?php wp_nonce_field('wpwps_settings_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="wpwps_printify_api_key">
                                    <?php echo esc_html__('API Key', 'wp-woocommerce-printify-sync'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="wpwps_printify_api_key" 
                                       name="wpwps_printify_api_key" 
                                       value="<?php echo esc_attr(get_option('wpwps_printify_api_key')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="wpwps_printify_endpoint">
                                    <?php echo esc_html__('API Endpoint', 'wp-woocommerce-printify-sync'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="wpwps_printify_endpoint" 
                                       name="wpwps_printify_endpoint" 
                                       value="<?php echo esc_attr(get_option('wpwps_printify_endpoint')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php echo esc_html__('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                        <button type="button" class="button wpwps-test-api" data-service="printify">
                            <?php echo esc_html__('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Geolocation API Settings -->
        <div class="wpwps-card" id="geolocation-settings">
            <div class="wpwps-card-header">
                <h2><?php echo esc_html__('Geolocation API Settings', 'wp-woocommerce-printify-sync'); ?></h2>
            </div>
            <div class="wpwps-card-body">
                <form class="wpwps-settings-form" data-section="geolocation">
                    <?php wp_nonce_field('wpwps_settings_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="wpwps_geolocation_api_key">
                                    <?php echo esc_html__('API Key', 'wp-woocommerce-printify-sync'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="wpwps_geolocation_api_key" 
                                       name="wpwps_geolocation_api_key" 
                                       value="<?php echo esc_attr(get_option('wpwps_geolocation_api_key')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="wpwps_geolocation_endpoint">
                                    <?php echo esc_html__('API Endpoint', 'wp-woocommerce-printify-sync'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="wpwps_geolocation_endpoint" 
                                       name="wpwps_geolocation_endpoint" 
                                       value="<?php echo esc_attr(get_option('wpwps_geolocation_endpoint')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php echo esc_html__('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                        <button type="button" class="button wpwps-test-api" data-service="geolocation">
                            <?php echo esc_html__('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Currency API Settings -->
        <div class="wpwps-card" id="currency-settings">
            <div class="wpwps-card-header">
                <h2><?php echo esc_html__('Currency API Settings', 'wp-woocommerce-printify-sync'); ?></h2>
            </div>
            <div class="wpwps-card-body">
                <form class="wpwps-settings-form" data-section="currency">
                    <?php wp_nonce_field('wpwps_settings_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="wpwps_currency_api_key">
                                    <?php echo esc_html__('API Key', 'wp-woocommerce-printify-sync'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="wpwps_currency_api_key" 
                                       name="wpwps_currency_api_key" 
                                       value="<?php echo esc_attr(get_option('wpwps_currency_api_key')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="wpwps_currency_endpoint">
                                    <?php echo esc_html__('API Endpoint', 'wp-woocommerce-printify-sync'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="wpwps_currency_endpoint" 
                                       name="wpwps_currency_endpoint" 
                                       value="<?php echo esc_attr(get_option('wpwps_currency_endpoint')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php echo esc_html__('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                        <button type="button" class="button wpwps-test-api" data-service="currency">
                            <?php echo esc_html__('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- R2 Settings -->
        <div class="wpwps-card" id="r2-settings">
            <div class="wpwps-card-header">
                <h2><?php echo esc_html__('R2 Storage Settings', 'wp-woocommerce-printify-sync'); ?></h2>
            </div>
            <div class="wpwps-card-body">
                <form class="wpwps-settings-form" data-section="r2">
                    <?php wp_nonce_field('wpwps_settings_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="wpwps_r2_account_id">
                                    <?php echo esc_html__('Account ID', 'wp-woocommerce-printify-sync'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="wpwps_r2_account_id" 
                                       name="wpwps_r2_account_id" 
                                       value="<?php echo esc_attr(get_option('wpwps_r2_account_id')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="wpwps_r2_access_key">
                                    <?php echo esc_html__('Access Key', 'wp-woocommerce-printify-sync'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="wpwps_r2_access_key" 
                                       name