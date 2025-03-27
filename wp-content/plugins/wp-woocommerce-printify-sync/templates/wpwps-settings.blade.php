@extends('layout')

@section('content')
<div class="wrap">
    <h1>{{ __('Printify Settings', 'wp-woocommerce-printify-sync') }}</h1>

    <div class="wpwps-card">
        <form id="wpwps-settings-form">
            <?php wp_nonce_field('wpwps_ajax_nonce', 'nonce'); ?>
            
            <div class="form-group">
                <label for="api_key">{{ __('Printify API Key', 'wp-woocommerce-printify-sync') }}</label>
                <input type="password" id="api_key" name="api_key" class="regular-text" value="{{ $settings['api_key'] }}">
                <button type="button" id="test-connection" class="button button-secondary">
                    {{ __('Test Connection', 'wp-woocommerce-printify-sync') }}
                </button>
            </div>

            <div class="form-group">
                <label for="api_endpoint">{{ __('API Endpoint', 'wp-woocommerce-printify-sync') }}</label>
                <input type="text" id="api_endpoint" name="api_endpoint" class="regular-text" value="{{ $settings['api_endpoint'] }}">
            </div>

            <div class="form-group">
                <label for="shop_id">{{ __('Shop', 'wp-woocommerce-printify-sync') }}</label>
                <select id="shop_id" name="shop_id" class="regular-text">
                    <option value="">{{ __('Select a shop...', 'wp-woocommerce-printify-sync') }}</option>
                </select>
            </div>

            <hr>

            <h2>{{ __('OpenAI Configuration', 'wp-woocommerce-printify-sync') }}</h2>

            <div class="form-group">
                <label for="openai_api_key">{{ __('OpenAI API Key', 'wp-woocommerce-printify-sync') }}</label>
                <input type="password" id="openai_api_key" name="openai_api_key" class="regular-text" value="{{ $settings['openai_api_key'] }}">
            </div>

            <div class="form-group">
                <label for="token_limit">{{ __('Token Limit', 'wp-woocommerce-printify-sync') }}</label>
                <input type="number" id="token_limit" name="token_limit" class="regular-text" value="{{ $settings['token_limit'] }}">
            </div>

            <div class="form-group">
                <label for="temperature">{{ __('Temperature', 'wp-woocommerce-printify-sync') }}</label>
                <input type="number" id="temperature" name="temperature" class="regular-text" step="0.1" min="0" max="1" value="{{ $settings['temperature'] }}">
            </div>

            <div class="form-group">
                <label for="monthly_cap">{{ __('Monthly Spend Cap (USD)', 'wp-woocommerce-printify-sync') }}</label>
                <input type="number" id="monthly_cap" name="monthly_cap" class="regular-text" value="{{ $settings['monthly_cap'] }}">
            </div>

            <div class="form-group">
                <button type="submit" class="button button-primary">{{ __('Save Settings', 'wp-woocommerce-printify-sync') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
jQuery(document).ready(function($) {
    $('#test-connection').on('click', function() {
        var button = $(this);
        var apiKey = $('#api_key').val();
        
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_test_connection',
                nonce: $('#nonce').val(),
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    
                    // Populate shops dropdown
                    var select = $('#shop_id');
                    select.empty();
                    select.append($('<option>', {
                        value: '',
                        text: '{{ __("Select a shop...", "wp-woocommerce-printify-sync") }}'
                    }));
                    
                    $.each(response.data.shops, function(i, shop) {
                        select.append($('<option>', {
                            value: shop.id,
                            text: shop.title
                        }));
                    });
                    
                    select.val('{{ $settings["shop_id"] }}');
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('{{ __("Connection failed!", "wp-woocommerce-printify-sync") }}');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    $('#wpwps-settings-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        
        submitButton.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_save_settings',
                nonce: $('#nonce').val(),
                api_key: $('#api_key').val(),
                api_endpoint: $('#api_endpoint').val(),
                shop_id: $('#shop_id').val(),
                openai_api_key: $('#openai_api_key').val(),
                token_limit: $('#token_limit').val(),
                temperature: $('#temperature').val(),
                monthly_cap: $('#monthly_cap').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('{{ __("Failed to save settings!", "wp-woocommerce-printify-sync") }}');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });
});
</script>
@endsection