@extends('layout')

@section('content')
<form method="post" action="options.php" class="mt-2">
    @wpnonce('wpwps_settings_nonce')
    <?php settings_fields('wpwps_settings'); ?>
    
    <div class="row">
        <!-- Printify Integration -->
        <div class="col-lg-6">
            <div class="card wpwps-card mb-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-plug me-2"></i> {{ __('Printify Integration', 'wp-woocommerce-printify-sync') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label for="wpwps_printify_api_key" class="form-label fw-medium">
                            {{ __('API Key', 'wp-woocommerce-printify-sync') }} <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control wpwps-form-control" 
                                   id="wpwps_printify_api_key" 
                                   name="wpwps_printify_api_key"
                                   value="{{ $settings['printify_api_key'] }}"
                                   required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('wpwps_printify_api_key')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            {{ __('Get this from your Printify account settings.', 'wp-woocommerce-printify-sync') }}
                        </small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="wpwps_printify_shop_id" class="form-label fw-medium">
                            {{ __('Shop ID', 'wp-woocommerce-printify-sync') }} <span class="text-danger">*</span>
                        </label>
                        <select class="form-select wpwps-form-control" 
                               id="wpwps_printify_shop_id" 
                               name="wpwps_printify_shop_id"
                               {{ !empty($settings['printify_shop_id']) ? 'disabled' : '' }}
                               required>
                            <option value="">{{ __('Select a shop', 'wp-woocommerce-printify-sync') }}</option>
                            @if(!empty($settings['printify_shop_id']))
                                <option value="{{ $settings['printify_shop_id'] }}" selected>
                                    {{ __('Current Shop', 'wp-woocommerce-printify-sync') }} (ID: {{ $settings['printify_shop_id'] }})
                                </option>
                            @endif
                        </select>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="button" id="test-printify" class="btn wpwps-btn wpwps-btn-primary">
                                <i class="fas fa-plug me-2"></i> {{ __('Test Connection', 'wp-woocommerce-printify-sync') }}
                            </button>
                            
                            @if(!empty($settings['printify_shop_id']))
                                <button type="button" class="btn wpwps-btn wpwps-btn-secondary" id="change-shop">
                                    <i class="fas fa-exchange-alt me-2"></i> {{ __('Change Shop', 'wp-woocommerce-printify-sync') }}
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-medium">{{ __('Webhook Settings', 'wp-woocommerce-printify-sync') }}</label>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_order_webhook" checked>
                                        <label class="form-check-label" for="enable_order_webhook">
                                            {{ __('Enable order webhooks', 'wp-woocommerce-printify-sync') }}
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        {{ __('Automatically send orders to Printify when placed in WooCommerce', 'wp-woocommerce-printify-sync') }}
                                    </small>
                                </div>
                                <div class="mb-0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_product_webhook" checked>
                                        <label class="form-check-label" for="enable_product_webhook">
                                            {{ __('Enable product webhooks', 'wp-woocommerce-printify-sync') }}
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        {{ __('Automatically update WooCommerce when products change in Printify', 'wp-woocommerce-printify-sync') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- OpenAI Integration -->
        <div class="col-lg-6">
            <div class="card wpwps-card mb-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-robot me-2"></i> {{ __('OpenAI Integration', 'wp-woocommerce-printify-sync') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ __('AI features help improve product descriptions, SEO, and customer service workflows.', 'wp-woocommerce-printify-sync') }}
                    </div>
                    
                    <div class="mb-4">
                        <label for="wpwps_openai_api_key" class="form-label fw-medium">
                            {{ __('API Key', 'wp-woocommerce-printify-sync') }}
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                  class="form-control wpwps-form-control" 
                                  id="wpwps_openai_api_key" 
                                  name="wpwps_openai_api_key"
                                  value="{{ $settings['openai_api_key'] }}">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('wpwps_openai_api_key')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            <a href="https://platform.openai.com/account/api-keys" target="_blank">
                                {{ __('Get an API key from OpenAI', 'wp-woocommerce-printify-sync') }}
                            </a>
                        </small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="wpwps_openai_token_limit" class="form-label fw-medium">
                            {{ __('Token Limit', 'wp-woocommerce-printify-sync') }}
                        </label>
                        <input type="number" 
                              class="form-control wpwps-form-control" 
                              id="wpwps_openai_token_limit" 
                              name="wpwps_openai_token_limit"
                              value="{{ $settings['openai_token_limit'] }}"
                              min="1"
                              max="4096">
                        <small class="text-muted d-block mt-2">
                            {{ __('Maximum tokens to generate per request. Higher values may increase costs.', 'wp-woocommerce-printify-sync') }}
                        </small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="wpwps_openai_temperature" class="form-label fw-medium">
                            {{ __('Temperature', 'wp-woocommerce-printify-sync') }}
                            <span class="text-muted ms-2" id="temp-value">{{ $settings['openai_temperature'] }}</span>
                        </label>
                        <input type="range" 
                              class="form-range" 
                              id="wpwps_openai_temperature" 
                              name="wpwps_openai_temperature"
                              value="{{ $settings['openai_temperature'] }}"
                              min="0"
                              max="1"
                              step="0.1"
                              oninput="document.getElementById('temp-value').textContent = this.value">
                        <div class="d-flex justify-content-between">
                            <small>{{ __('Precise', 'wp-woocommerce-printify-sync') }}</small>
                            <small>{{ __('Creative', 'wp-woocommerce-printify-sync') }}</small>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="wpwps_openai_spend_cap" class="form-label fw-medium">
                            {{ __('Monthly Spend Cap ($)', 'wp-woocommerce-printify-sync') }}
                        </label>
                        <input type="number" 
                              class="form-control wpwps-form-control" 
                              id="wpwps_openai_spend_cap" 
                              name="wpwps_openai_spend_cap"
                              value="{{ $settings['openai_spend_cap'] }}"
                              min="0">
                        <small class="text-muted d-block mt-2">
                            {{ __('Set a monthly spending limit to prevent unexpected costs', 'wp-woocommerce-printify-sync') }}
                        </small>
                    </div>
                    
                    <button type="button" class="btn wpwps-btn wpwps-btn-primary" id="test-openai">
                        <i class="fas fa-plug me-2"></i> {{ __('Test Connection', 'wp-woocommerce-printify-sync') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Advanced Settings -->
    <div class="row">
        <div class="col-12">
            <div class="card wpwps-card mb-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-cogs me-2"></i> {{ __('Advanced Settings', 'wp-woocommerce-printify-sync') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ __('Sync Frequency', 'wp-woocommerce-printify-sync') }}</label>
                                <select class="form-select wpwps-form-control">
                                    <option value="hourly">{{ __('Hourly', 'wp-woocommerce-printify-sync') }}</option>
                                    <option value="twicedaily" selected>{{ __('Twice Daily', 'wp-woocommerce-printify-sync') }}</option>
                                    <option value="daily">{{ __('Daily', 'wp-woocommerce-printify-sync') }}</option>
                                    <option value="manual">{{ __('Manual Only', 'wp-woocommerce-printify-sync') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ __('Log Level', 'wp-woocommerce-printify-sync') }}</label>
                                <select class="form-select wpwps-form-control">
                                    <option value="none">{{ __('None', 'wp-woocommerce-printify-sync') }}</option>
                                    <option value="error">{{ __('Errors Only', 'wp-woocommerce-printify-sync') }}</option>
                                    <option value="warning" selected>{{ __('Warnings & Errors', 'wp-woocommerce-printify-sync') }}</option>
                                    <option value="info">{{ __('Info (Verbose)', 'wp-woocommerce-printify-sync') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="enable_debug_mode">
                        <label class="form-check-label" for="enable_debug_mode">
                            {{ __('Enable Debug Mode', 'wp-woocommerce-printify-sync') }}
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1 mb-3">
                        {{ __('Adds additional logging for troubleshooting issues', 'wp-woocommerce-printify-sync') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card wpwps-card">
                <div class="card-body text-center">
                    <button type="submit" class="btn wpwps-btn wpwps-btn-primary px-5">
                        <i class="fas fa-save me-2"></i> {{ __('Save All Settings', 'wp-woocommerce-printify-sync') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    window.togglePasswordVisibility = function(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.parentNode.querySelector('.fa-eye, .fa-eye-slash');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    };
    
    // Change shop handler
    const changeShopBtn = document.getElementById('change-shop');
    if (changeShopBtn) {
        changeShopBtn.addEventListener('click', function() {
            const shopSelect = document.getElementById('wpwps_printify_shop_id');
            shopSelect.disabled = false;
            shopSelect.innerHTML = '<option value="">{{ __("Select a shop", "wp-woocommerce-printify-sync") }}</option>';
            this.disabled = true;
        });
    }
    
    // Test API connections
    const testPrintify = document.getElementById('test-printify');
    const testOpenAI = document.getElementById('test-openai');
    
    if (testPrintify) {
        testPrintify.addEventListener('click', async function() {
            const button = this;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> {{ __("Testing...", "wp-woocommerce-printify-sync") }}';
            
            try {
                const apiKey = document.getElementById('wpwps_printify_api_key').value;
                if (!apiKey) {
                    wpwpsShowToast('{{ __("Error", "wp-woocommerce-printify-sync") }}', '{{ __("API key is required", "wp-woocommerce-printify-sync") }}', 'danger');
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-plug me-2"></i> {{ __("Test Connection", "wp-woocommerce-printify-sync") }}';
                    return;
                }
                
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'wpwps_test_printify',
                        _wpnonce: document.querySelector('[name="_wpnonce"]').value
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    wpwpsShowToast('{{ __("Success", "wp-woocommerce-printify-sync") }}', data.data.message, 'success');
                    
                    // If shops data is available, populate shop dropdown
                    if (data.data.shops && data.data.shops.length > 0) {
                        const shopSelect = document.getElementById('wpwps_printify_shop_id');
                        if (shopSelect && !shopSelect.disabled) {
                            shopSelect.innerHTML = '<option value="">{{ __("Select a shop", "wp-woocommerce-printify-sync") }}</option>';
                            data.data.shops.forEach(shop => {
                                const option = document.createElement('option');
                                option.value = shop.id;
                                option.textContent = shop.title;
                                shopSelect.appendChild(option);
                            });
                        }
                    }
                } else {
                    wpwpsShowToast('{{ __("Error", "wp-woocommerce-printify-sync") }}', data.data.message, 'danger');
                }
            } catch (error) {
                wpwpsShowToast('{{ __("Error", "wp-woocommerce-printify-sync") }}', '{{ __("Connection failed. Please try again.", "wp-woocommerce-printify-sync") }}', 'danger');
            } finally {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-plug me-2"></i> {{ __("Test Connection", "wp-woocommerce-printify-sync") }}';
            }
        });
    }
    
    if (testOpenAI) {
        testOpenAI.addEventListener('click', async function() {
            const button = this;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> {{ __("Testing...", "wp-woocommerce-printify-sync") }}';
            
            try {
                const apiKey = document.getElementById('wpwps_openai_api_key').value;
                if (!apiKey) {
                    wpwpsShowToast('{{ __("Error", "wp-woocommerce-printify-sync") }}', '{{ __("API key is required", "wp-woocommerce-printify-sync") }}', 'danger');
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-plug me-2"></i> {{ __("Test Connection", "wp-woocommerce-printify-sync") }}';
                    return;
                }
                
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'wpwps_test_openai',
                        _wpnonce: document.querySelector('[name="_wpnonce"]').value
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    wpwpsShowToast('{{ __("Success", "wp-woocommerce-printify-sync") }}', data.data.message, 'success');
                } else {
                    wpwpsShowToast('{{ __("Error", "wp-woocommerce-printify-sync") }}', data.data.message, 'danger');
                }
            } catch (error) {
                wpwpsShowToast('{{ __("Error", "wp-woocommerce-printify-sync") }}', '{{ __("Connection failed. Please try again.", "wp-woocommerce-printify-sync") }}', 'danger');
            } finally {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-plug me-2"></i> {{ __("Test Connection", "wp-woocommerce-printify-sync") }}';
            }
        });
    }
});
</script>
@endsection