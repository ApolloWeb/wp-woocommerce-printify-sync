@extends('layout')

@section('content')
<div class="wpwps-settings-container">
    <div class="wpwps-settings-header">
        <h1 class="wpwps-settings-title">{{ __('Printify Sync Settings', 'wp-woocommerce-printify-sync') }}</h1>
        <p class="wpwps-settings-description">{{ __('Configure your Printify integration settings and synchronization options.', 'wp-woocommerce-printify-sync') }}</p>
    </div>

    @if(isset($connection_status))
        <div class="wpwps-settings-alert wpwps-settings-alert-{{ $connection_status['type'] }}">
            <i class="fas fa-{{ $connection_status['icon'] }}"></i>
            <div>
                <strong>{{ $connection_status['title'] }}</strong>
                <p class="mb-0">{{ $connection_status['message'] }}</p>
            </div>
        </div>
    @endif

    <form method="post" action="options.php" class="wpwps-settings-form">
        @php settings_fields('wpwps_settings') @endphp

        <!-- API Settings Section -->
        <div class="wpwps-settings-section">
            <h2 class="wpwps-settings-section-title">{{ __('API Configuration', 'wp-woocommerce-printify-sync') }}</h2>
            <p class="wpwps-settings-section-description">
                {{ __('Enter your Printify API credentials. You can find these in your Printify dashboard under Settings > API.', 'wp-woocommerce-printify-sync') }}
            </p>

            <div class="wpwps-settings-field">
                <label for="wpwps_api_key" class="wpwps-settings-label">{{ __('API Key', 'wp-woocommerce-printify-sync') }}</label>
                <input 
                    type="password" 
                    id="wpwps_api_key" 
                    name="wpwps_settings[api_key]" 
                    value="{{ esc_attr($settings['api_key'] ?? '') }}" 
                    class="wpwps-settings-input"
                >
                <p class="wpwps-settings-description">
                    {{ __('Your Printify API key for authentication.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>

            <div class="wpwps-settings-field">
                <label for="wpwps_shop_id" class="wpwps-settings-label">{{ __('Shop ID', 'wp-woocommerce-printify-sync') }}</label>
                <input 
                    type="text" 
                    id="wpwps_shop_id" 
                    name="wpwps_settings[shop_id]" 
                    value="{{ esc_attr($settings['shop_id'] ?? '') }}" 
                    class="wpwps-settings-input"
                >
                <p class="wpwps-settings-description">
                    {{ __('Your Printify Shop ID. You can find this in your shop URL or settings.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>

            <button type="button" class="wpwps-settings-btn wpwps-settings-btn-secondary test-connection">
                <i class="fas fa-plug"></i> {{ __('Test Connection', 'wp-woocommerce-printify-sync') }}
            </button>

            <div class="wpwps-settings-connection-status {{ !empty($settings['api_key']) && !empty($settings['shop_id']) ? 'connected' : 'disconnected' }}">
                <i class="fas fa-{{ !empty($settings['api_key']) && !empty($settings['shop_id']) ? 'check-circle' : 'times-circle' }}"></i>
                <span>{{ !empty($settings['api_key']) && !empty($settings['shop_id']) ? __('Connected', 'wp-woocommerce-printify-sync') : __('Not Connected', 'wp-woocommerce-printify-sync') }}</span>
            </div>
        </div>

        <!-- Printify Configuration Section -->
        <div class="wpwps-settings-section">
            <h2 class="wpwps-settings-section-title">{{ __('Printify Configuration', 'wp-woocommerce-printify-sync') }}</h2>
            <p class="wpwps-settings-section-description">
                {{ __('Configure your Printify API credentials and shop settings.', 'wp-woocommerce-printify-sync') }}
            </p>

            <div class="wpwps-settings-field">
                <label for="wpwps_printify_api_key" class="wpwps-settings-label">{{ __('API Key', 'wp-woocommerce-printify-sync') }}</label>
                <input 
                    type="password" 
                    id="wpwps_printify_api_key" 
                    name="wpwps_settings[printify_api_key]" 
                    value="{{ esc_attr($settings['printify_api_key'] ?? '') }}" 
                    class="wpwps-settings-input"
                >
                <p class="wpwps-settings-description">
                    {{ __('Your Printify API key for authentication.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>

            <div class="wpwps-settings-field">
                <label for="wpwps_printify_endpoint" class="wpwps-settings-label">{{ __('API Endpoint', 'wp-woocommerce-printify-sync') }}</label>
                <input 
                    type="text" 
                    id="wpwps_printify_endpoint" 
                    name="wpwps_settings[printify_endpoint]" 
                    value="{{ esc_attr($settings['printify_endpoint'] ?? 'https://api.printify.com/v1/') }}" 
                    class="wpwps-settings-input"
                >
                <p class="wpwps-settings-description">
                    {{ __('The Printify API endpoint. Defaults to https://api.printify.com/v1/.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>

            <button type="button" class="wpwps-settings-btn wpwps-settings-btn-secondary test-printify-connection">
                <i class="fas fa-plug"></i> {{ __('Test Connection', 'wp-woocommerce-printify-sync') }}
            </button>

            <div class="wpwps-settings-field">
                <label for="wpwps_printify_shop" class="wpwps-settings-label">{{ __('Select Shop', 'wp-woocommerce-printify-sync') }}</label>
                <select 
                    id="wpwps_printify_shop" 
                    name="wpwps_settings[printify_shop]" 
                    class="wpwps-settings-select"
                    {{ isset($settings['printify_shop']) ? 'disabled' : '' }}
                >
                    @if(isset($shops))
                        @foreach($shops as $shop)
                            <option value="{{ $shop['id'] }}" {{ ($settings['printify_shop'] ?? '') === $shop['id'] ? 'selected' : '' }}>
                                {{ $shop['title'] }}
                            </option>
                        @endforeach
                    @endif
                </select>
                <p class="wpwps-settings-description">
                    {{ __('Select your Printify shop. Once saved, this cannot be changed.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>
        </div>

        <!-- OpenAI Configuration Section -->
        <div class="wpwps-settings-section">
            <h2 class="wpwps-settings-section-title">{{ __('OpenAI Configuration', 'wp-woocommerce-printify-sync') }}</h2>
            <p class="wpwps-settings-section-description">
                {{ __('Configure your OpenAI API credentials and settings.', 'wp-woocommerce-printify-sync') }}
            </p>

            <div class="wpwps-settings-field">
                <label for="wpwps_openai_api_key" class="wpwps-settings-label">{{ __('API Key', 'wp-woocommerce-printify-sync') }}</label>
                <input 
                    type="password" 
                    id="wpwps_openai_api_key" 
                    name="wpwps_settings[openai_api_key]" 
                    value="{{ esc_attr($settings['openai_api_key'] ?? '') }}" 
                    class="wpwps-settings-input"
                >
                <p class="wpwps-settings-description">
                    {{ __('Your OpenAI API key for authentication.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>

            <div class="wpwps-settings-field">
                <label for="wpwps_openai_tokens" class="wpwps-settings-label">{{ __('Tokens', 'wp-woocommerce-printify-sync') }}</label>
                <input 
                    type="number" 
                    id="wpwps_openai_tokens" 
                    name="wpwps_settings[openai_tokens]" 
                    value="{{ esc_attr($settings['openai_tokens'] ?? 1000) }}" 
                    class="wpwps-settings-input"
                >
                <p class="wpwps-settings-description">
                    {{ __('The number of tokens to use for each request.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>

            <div class="wpwps-settings-field">
                <label for="wpwps_openai_temperature" class="wpwps-settings-label">{{ __('Temperature', 'wp-woocommerce-printify-sync') }}</label>
                <input 
                    type="range" 
                    id="wpwps_openai_temperature" 
                    name="wpwps_settings[openai_temperature]" 
                    value="{{ esc_attr($settings['openai_temperature'] ?? 0.7) }}" 
                    min="0" 
                    max="1" 
                    step="0.1" 
                    class="wpwps-settings-input"
                >
                <p class="wpwps-settings-description">
                    {{ __('Adjust the randomness of the AI responses. Higher values mean more random responses.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>

            <div class="wpwps-settings-field">
                <label for="wpwps_openai_spend_cap" class="wpwps-settings-label">{{ __('Spend Cap', 'wp-woocommerce-printify-sync') }}</label>
                <input 
                    type="number" 
                    id="wpwps_openai_spend_cap" 
                    name="wpwps_settings[openai_spend_cap]" 
                    value="{{ esc_attr($settings['openai_spend_cap'] ?? 50) }}" 
                    class="wpwps-settings-input"
                >
                <p class="wpwps-settings-description">
                    {{ __('Set a monthly spend cap in USD.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>

            <button type="button" class="wpwps-settings-btn wpwps-settings-btn-secondary test-openai-connection">
                <i class="fas fa-plug"></i> {{ __('Test Connection', 'wp-woocommerce-printify-sync') }}
            </button>
        </div>

        <!-- Sync Settings Section -->
        <div class="wpwps-settings-section">
            <h2 class="wpwps-settings-section-title">{{ __('Sync Settings', 'wp-woocommerce-printify-sync') }}</h2>
            <p class="wpwps-settings-section-description">
                {{ __('Configure how and when your products and orders should synchronize with Printify.', 'wp-woocommerce-printify-sync') }}
            </p>

            <div class="wpwps-settings-field">
                <label class="wpwps-settings-checkbox-label">
                    <input 
                        type="checkbox" 
                        name="wpwps_settings[auto_sync]" 
                        value="1" 
                        class="wpwps-settings-checkbox"
                        {{ isset($settings['auto_sync']) && $settings['auto_sync'] ? 'checked' : '' }}
                    >
                    {{ __('Enable Automatic Synchronization', 'wp-woocommerce-printify-sync') }}
                </label>
                <p class="wpwps-settings-description">
                    {{ __('Automatically sync products and orders based on the selected interval.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>

            <div class="wpwps-settings-field">
                <label for="wpwps_sync_interval" class="wpwps-settings-label">{{ __('Sync Interval', 'wp-woocommerce-printify-sync') }}</label>
                <select 
                    id="wpwps_sync_interval" 
                    name="wpwps_settings[sync_interval]" 
                    class="wpwps-settings-select"
                >
                    <option value="hourly" {{ ($settings['sync_interval'] ?? '') === 'hourly' ? 'selected' : '' }}>
                        {{ __('Hourly', 'wp-woocommerce-printify-sync') }}
                    </option>
                    <option value="twicedaily" {{ ($settings['sync_interval'] ?? '') === 'twicedaily' ? 'selected' : '' }}>
                        {{ __('Twice Daily', 'wp-woocommerce-printify-sync') }}
                    </option>
                    <option value="daily" {{ ($settings['sync_interval'] ?? '') === 'daily' ? 'selected' : '' }}>
                        {{ __('Daily', 'wp-woocommerce-printify-sync') }}
                    </option>
                </select>
            </div>

            <div class="wpwps-settings-field">
                <label class="wpwps-settings-label">{{ __('Sync Options', 'wp-woocommerce-printify-sync') }}</label>
                <div class="wpwps-settings-checkbox-group">
                    <label class="wpwps-settings-checkbox-label">
                        <input 
                            type="checkbox" 
                            name="wpwps_settings[sync_products]" 
                            value="1" 
                            class="wpwps-settings-checkbox"
                            {{ isset($settings['sync_products']) && $settings['sync_products'] ? 'checked' : '' }}
                        >
                        {{ __('Sync Products', 'wp-woocommerce-printify-sync') }}
                    </label>
                    <label class="wpwps-settings-checkbox-label">
                        <input 
                            type="checkbox" 
                            name="wpwps_settings[sync_inventory]" 
                            value="1" 
                            class="wpwps-settings-checkbox"
                            {{ isset($settings['sync_inventory']) && $settings['sync_inventory'] ? 'checked' : '' }}
                        >
                        {{ __('Sync Inventory', 'wp-woocommerce-printify-sync') }}
                    </label>
                    <label class="wpwps-settings-checkbox-label">
                        <input 
                            type="checkbox" 
                            name="wpwps_settings[sync_orders]" 
                            value="1" 
                            class="wpwps-settings-checkbox"
                            {{ isset($settings['sync_orders']) && $settings['sync_orders'] ? 'checked' : '' }}
                        >
                        {{ __('Sync Orders', 'wp-woocommerce-printify-sync') }}
                    </label>
                </div>
                <p class="wpwps-settings-description">
                    {{ __('Select which items should be synchronized with Printify.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>
        </div>

        <!-- Advanced Settings Section -->
        <div class="wpwps-settings-section">
            <h2 class="wpwps-settings-section-title">{{ __('Advanced Settings', 'wp-woocommerce-printify-sync') }}</h2>
            <p class="wpwps-settings-section-description">
                {{ __('Configure advanced options for the integration.', 'wp-woocommerce-printify-sync') }}
            </p>

            <div class="wpwps-settings-field">
                <label class="wpwps-settings-checkbox-label">
                    <input 
                        type="checkbox" 
                        name="wpwps_settings[debug_mode]" 
                        value="1" 
                        class="wpwps-settings-checkbox"
                        {{ isset($settings['debug_mode']) && $settings['debug_mode'] ? 'checked' : '' }}
                    >
                    {{ __('Enable Debug Mode', 'wp-woocommerce-printify-sync') }}
                </label>
                <p class="wpwps-settings-description">
                    {{ __('Log detailed synchronization information for troubleshooting.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>

            <div class="wpwps-settings-field">
                <label class="wpwps-settings-checkbox-label">
                    <input 
                        type="checkbox" 
                        name="wpwps_settings[error_notifications]" 
                        value="1" 
                        class="wpwps-settings-checkbox"
                        {{ isset($settings['error_notifications']) && $settings['error_notifications'] ? 'checked' : '' }}
                    >
                    {{ __('Enable Error Notifications', 'wp-woocommerce-printify-sync') }}
                </label>
                <p class="wpwps-settings-description">
                    {{ __('Receive email notifications when synchronization errors occur.', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>
        </div>

        <div class="wpwps-settings-actions">
            <button type="submit" class="wpwps-settings-btn wpwps-settings-btn-primary">
                <i class="fas fa-save"></i> {{ __('Save Settings', 'wp-woocommerce-printify-sync') }}
            </button>
        </div>
    </form>
</div>
@endsection