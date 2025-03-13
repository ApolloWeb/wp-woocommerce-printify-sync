<div class="wrap wpps-dashboard">
    <h1><i class="fas fa-cog wpps-icon"></i> {{ $title }}</h1>
    
    <div id="wpps-settings-message"></div>
    
    <div class="wpps-widget">
        <form method="post" class="wpps-settings-form">
            <h2><i class="fas fa-key wpps-icon"></i> {{ __('API Settings', 'wp-woocommerce-printify-sync') }}</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wpps_printify_api_key">
                            <i class="fas fa-tshirt wpps-icon"></i> {{ __('Printify API Key', 'wp-woocommerce-printify-sync') }}
                        </label>
                    </th>
                    <td>
                        <input 
                            type="password" 
                            id="wpps_printify_api_key" 
                            name="printify_api_key" 
                            class="regular-text"
                            value="{{ $settings['printify_api_key'] ?? '' }}"
                        />
                        <button 
                            type="button" 
                            class="button wpps-test-api-button" 
                            data-api="printify"
                        >{{ __('Test Connection', 'wp-woocommerce-printify-sync') }}</button>
                        <div id="wpps-printify-test-result"></div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpps_geolocation_api_key">
                            <i class="fas fa-map-marker-alt wpps-icon"></i> {{ __('Geolocation API Key', 'wp-woocommerce-printify-sync') }}
                        </label>
                    </th>
                    <td>
                        <input 
                            type="password" 
                            id="wpps_geolocation_api_key" 
                            name="geolocation_api_key" 
                            class="regular-text"
                            value="{{ $settings['geolocation_api_key'] ?? '' }}"
                        />
                        <button 
                            type="button" 
                            class="button wpps-test-api-button" 
                            data-api="geolocation"
                        >{{ __('Test Connection', 'wp-woocommerce-printify-sync') }}</button>
                        <div id="wpps-geolocation-test-result"></div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpps_currency_api_key">
                            <i class="fas fa-exchange-alt wpps-icon"></i> {{ __('Currency API Key', 'wp-woocommerce-printify-sync') }}
                        </label>
                    </th>
                    <td>
                        <input 
                            type="password" 
                            id="wpps_currency_api_key" 
                            name="currency_api_key" 
                            class="regular-text"
                            value="{{ $settings['currency_api_key'] ?? '' }}"
                        />
                        <button 
                            type="button" 
                            class="button wpps-test-api-button" 
                            data-api="currency"
                        >{{ __('Test Connection', 'wp-woocommerce-printify-sync') }}</button>
                        <div id="wpps-currency-test-result"></div>
                    </td>
                </tr>
            </table>
            
            <h2><i class="fas fa-sync-alt wpps-icon"></i> {{ __('Sync Settings', 'wp-woocommerce-printify-sync') }}</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wpps_auto_sync_products">
                            <i class="fas fa-magic wpps-icon"></i> {{ __('Auto Sync Products', 'wp-woocommerce-printify-sync') }}
                        </label>
                    </th>
                    <td>
                        <label>
                            <input 
                                type="checkbox" 
                                id="wpps_auto_sync_products" 
                                name="auto_sync_products" 
                                value="1"
                                @if(!empty($settings['auto_sync_products'])) checked @endif
                            />
                            {{ __('Automatically sync products from Printify', 'wp-woocommerce-printify-sync') }}
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpps_sync_interval">
                            <i class="fas fa-clock wpps-icon"></i> {{ __('Sync Interval', 'wp-woocommerce-printify-sync') }}
                        </label>
                    </th>
                    <td>
                        <select id="wpps_sync_interval" name="sync_interval" class="regular-text">
                            <option value="hourly" @if(($settings['sync_interval'] ?? '') === 'hourly') selected @endif>
                                {{ __('Hourly', 'wp-woocommerce-printify-sync') }}
                            </option>
                            <option value="twicedaily" @if(($settings['sync_interval'] ?? '') === 'twicedaily') selected @endif>
                                {{ __('Twice Daily', 'wp-woocommerce-printify-sync') }}
                            </option>
                            <option value="daily" @if(($settings['sync_interval'] ?? '') === 'daily') selected @endif>
                                {{ __('Daily', 