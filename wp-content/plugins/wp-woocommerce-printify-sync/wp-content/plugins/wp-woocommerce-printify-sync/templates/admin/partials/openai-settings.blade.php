<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="card-title mb-0">{{ __('OpenAI API Settings', 'wp-woocommerce-printify-sync') }}</h5>
    </div>
    <div class="card-body">
        <p class="mb-3">{{ __('Configure OpenAI GPT-3.5-Turbo for ticket analysis and response suggestions.', 'wp-woocommerce-printify-sync') }}</p>
        
        <form id="wpwps-openai-settings-form">
            <div class="mb-3">
                <label for="openai_api_key" class="form-label">{{ __('OpenAI API Key', 'wp-woocommerce-printify-sync') }}</label>
                <input type="password" class="form-control" id="openai_api_key" name="openai_api_key" value="{{ $openai_api_key }}">
                <small class="form-text text-muted">{{ __('Your OpenAI API key will be stored securely using encryption.', 'wp-woocommerce-printify-sync') }}</small>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="openai_token_limit" class="form-label">{{ __('Max Tokens', 'wp-woocommerce-printify-sync') }}</label>
                        <input type="number" class="form-control" id="openai_token_limit" name="openai_token_limit" value="{{ $openai_token_limit }}" min="100" max="4000">
                        <small class="form-text text-muted">{{ __('Maximum number of tokens per request.', 'wp-woocommerce-printify-sync') }}</small>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="openai_temperature" class="form-label">{{ __('Temperature', 'wp-woocommerce-printify-sync') }}</label>
                        <input type="number" class="form-control" id="openai_temperature" name="openai_temperature" value="{{ $openai_temperature }}" min="0" max="2" step="0.1">
                        <small class="form-text text-muted">{{ __('Controls randomness (0-2). Lower values are more focused.', 'wp-woocommerce-printify-sync') }}</small>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="openai_spend_cap" class="form-label">{{ __('Monthly Spend Cap ($)', 'wp-woocommerce-printify-sync') }}</label>
                        <input type="number" class="form-control" id="openai_spend_cap" name="openai_spend_cap" value="{{ $openai_spend_cap }}" min="0" step="0.01">
                        <small class="form-text text-muted">{{ __('Set a monthly budget limit for AI usage.', 'wp-woocommerce-printify-sync') }}</small>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <button type="button" id="wpwps-test-openai" class="btn btn-info">
                    <i class="fas fa-sync-alt"></i> {{ __('Test Connection & Estimate Cost', 'wp-woocommerce-printify-sync') }}
                </button>
                <span id="wpwps-openai-test-result" class="ms-2"></span>
            </div>
            
            <div id="openai-cost-estimate" class="alert alert-info" style="display: none;"></div>
            
            <button type="submit" class="btn btn-primary">{{ __('Save Settings', 'wp-woocommerce-printify-sync') }}</button>
        </form>
    </div>
</div>
