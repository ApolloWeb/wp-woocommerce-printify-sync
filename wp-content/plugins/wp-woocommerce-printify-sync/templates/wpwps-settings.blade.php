@extends('layout')

@section('content')
<div class="container-fluid">
    <form method="post" action="options.php" class="mt-4">
        @wpnonce('wpwps_settings_nonce')
        <?php settings_fields('wpwps_settings'); ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-plug"></i> {{ __('Printify Integration', 'wp-woocommerce-printify-sync') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="wpwps_printify_api_key" class="form-label">{{ __('API Key', 'wp-woocommerce-printify-sync') }}</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="wpwps_printify_api_key" 
                                   name="wpwps_printify_api_key"
                                   value="{{ $settings['printify_api_key'] }}">
                        </div>
                        <div class="mb-3">
                            <label for="wpwps_printify_shop_id" class="form-label">{{ __('Shop ID', 'wp-woocommerce-printify-sync') }}</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="wpwps_printify_shop_id" 
                                   name="wpwps_printify_shop_id"
                                   value="{{ $settings['printify_shop_id'] }}">
                        </div>
                        <button type="button" class="btn btn-info" id="test-printify">
                            {{ __('Test Connection', 'wp-woocommerce-printify-sync') }}
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-robot"></i> {{ __('OpenAI Integration', 'wp-woocommerce-printify-sync') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="wpwps_openai_api_key" class="form-label">{{ __('API Key', 'wp-woocommerce-printify-sync') }}</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="wpwps_openai_api_key" 
                                   name="wpwps_openai_api_key"
                                   value="{{ $settings['openai_api_key'] }}">
                        </div>
                        <div class="mb-3">
                            <label for="wpwps_openai_token_limit" class="form-label">{{ __('Token Limit', 'wp-woocommerce-printify-sync') }}</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="wpwps_openai_token_limit" 
                                   name="wpwps_openai_token_limit"
                                   value="{{ $settings['openai_token_limit'] }}"
                                   min="1"
                                   max="4096">
                        </div>
                        <div class="mb-3">
                            <label for="wpwps_openai_temperature" class="form-label">{{ __('Temperature', 'wp-woocommerce-printify-sync') }}</label>
                            <input type="range" 
                                   class="form-range" 
                                   id="wpwps_openai_temperature" 
                                   name="wpwps_openai_temperature"
                                   value="{{ $settings['openai_temperature'] }}"
                                   min="0"
                                   max="1"
                                   step="0.1">
                            <div class="d-flex justify-content-between">
                                <small>{{ __('Precise', 'wp-woocommerce-printify-sync') }}</small>
                                <small>{{ __('Creative', 'wp-woocommerce-printify-sync') }}</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="wpwps_openai_spend_cap" class="form-label">{{ __('Monthly Spend Cap ($)', 'wp-woocommerce-printify-sync') }}</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="wpwps_openai_spend_cap" 
                                   name="wpwps_openai_spend_cap"
                                   value="{{ $settings['openai_spend_cap'] }}"
                                   min="0">
                        </div>
                        <button type="button" class="btn btn-info" id="test-openai">
                            {{ __('Test Connection', 'wp-woocommerce-printify-sync') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    {{ __('Save Changes', 'wp-woocommerce-printify-sync') }}
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testPrintify = document.getElementById('test-printify');
    const testOpenAI = document.getElementById('test-openai');
    
    testPrintify.addEventListener('click', async function() {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'wpwps_test_printify',
                    _wpnonce: document.querySelector('[name="_wpnonce"]').value
                })
            });
            
            const data = await response.json();
            alert(data.data.message);
        } catch (error) {
            alert('Error testing connection');
        }
    });
    
    testOpenAI.addEventListener('click', async function() {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'wpwps_test_openai',
                    _wpnonce: document.querySelector('[name="_wpnonce"]').value
                })
            });
            
            const data = await response.json();
            alert(data.data.message);
        } catch (error) {
            alert('Error testing connection');
        }
    });
});
</script>
@endsection