<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

class GeoCurrencyTab
{
    private ConfigService $config;
    private GeolocationService $geoService;
    private CurrencyService $currencyService;

    public function __construct(
        ConfigService $config,
        GeolocationService $geoService,
        CurrencyService $currencyService
    ) {
        $this->config = $config;
        $this->geoService = $geoService;
        $this->currencyService = $currencyService;
    }

    public function render(): void
    {
        ?>
        <div class="wpwps-card">
            <div class="card-body">
                <h5 class="card-title">Geolocation & Currency Settings</h5>
                
                <form id="geo-currency-settings" method="post" action="options.php">
                    <?php settings_fields('wpwps_geo_currency_settings'); ?>
                    
                    <!-- API Keys Section -->
                    <div class="mb-4">
                        <h6>API Configuration</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">IP Geolocation API Key</label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           name="wpwps_ipgeolocation_api_key"
                                           value="<?php echo esc_attr($this->config->get('ipgeolocation_api_key')); ?>">
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Currency API Key</label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           name="wpwps_currency_api_key"
                                           value="<?php echo esc_attr($this->config->get('currency_api_key')); ?>">
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features Configuration -->
                    <div class="mb-4">
                        <h6>Features Configuration</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="enable-geolocation"
                                           name="wpwps_enable_geolocation"
                                           <?php checked($this->config->get('enable_geolocation')); ?>>
                                    <label class="form-check-label" for="enable-geolocation">
                                        Enable Geolocation Detection
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="enable-auto-currency"
                                           name="wpwps_enable_auto_currency"
                                           <?php checked($this->config->get('enable_auto_currency')); ?>>
                                    <label class="form-check-label" for="enable-auto-currency">
                                        Enable Automatic Currency Conversion
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Currency Settings -->
                    <div class="mb-4">
                        <h6>Currency Settings</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Default Currency</label>
                                <select class="form-select" name="wpwps_default_currency">
                                    <?php
                                    $currencies = get_woocommerce_currencies();
                                    $defaultCurrency = $this->config->get('default_currency', 'USD');
                                    foreach ($currencies as $code => $name) {
                                        printf(
                                            '<option value="%s" %s>%s (%s)</option>',
                                            esc_attr($code),
                                            selected($defaultCurrency, $code, false),
                                            esc_html($name),
                                            esc_html($code)
                                        );
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cache Duration (hours)</label>
                                <input type="number" 
                                       class="form-control" 
                                       name="wpwps_cache_duration"
                                       value="<?php echo esc_attr($this->config->get('cache_duration') / 3600); ?>"
                                       min="1" 
                                       max="72">
                            </div>
                        </div>
                    </div>

                    <!-- Test Configuration -->
                    <div class="mb-4">
                        <h6>Test Configuration</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <button type="button" 
                                        class="btn btn-outline-primary" 
                                        id="test-geolocation">
                                    Test Geolocation
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-primary" 
                                        id="test-currency">
                                    Test Currency Conversion
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php submit_button('Save Settings'); ?>
                </form>
            </div>
        </div>

        <!-- Test Results Modal -->
        <div class="modal fade" id="test-results-modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Test Results</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <pre><code id="test-results"></code></pre>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}