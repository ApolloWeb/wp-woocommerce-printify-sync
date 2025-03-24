<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">{{ __('Printify API Settings', 'wp-woocommerce-printify-sync') }}</h5>
    </div>
    <div class="card-body">
        <form id="wpwps-printify-settings-form">
            <div class="mb-3">
                <label for="printify_api_key" class="form-label">{{ __('API Key', 'wp-woocommerce-printify-sync') }} *</label>
                <input type="password" class="form-control" id="printify_api_key" name="printify_api_key" value="{{ $printify_api_key }}" required>
                <small class="form-text text-muted">{{ __('Your Printify API key will be stored securely using encryption.', 'wp-woocommerce-printify-sync') }}</small>
            </div>
            
            <div class="mb-3">
                <label for="printify_api_endpoint" class="form-label">{{ __('API Endpoint', 'wp-woocommerce-printify-sync') }}</label>
                <input type="url" class="form-control" id="printify_api_endpoint" name="printify_api_endpoint" value="{{ $printify_api_endpoint }}">
                <small class="form-text text-muted">{{ __('Default: https://api.printify.com/v1', 'wp-woocommerce-printify-sync') }}</small>
            </div>
            
            <div class="mb-3">
                <button type="button" id="wpwps-test-printify" class="btn btn-info">
                    <i class="fas fa-sync-alt"></i> {{ __('Test Connection', 'wp-woocommerce-printify-sync') }}
                </button>
                <span id="wpwps-printify-test-result" class="ms-2"></span>
            </div>
            
            <div class="mb-3" id="printify-shops-container" style="display: none;">
                <label for="printify_shop_id" class="form-label">{{ __('Select Shop', 'wp-woocommerce-printify-sync') }}</label>
                <select class="form-select" id="printify_shop_id" name="printify_shop_id">
                    <option value="">{{ __('-- Select a shop --', 'wp-woocommerce-printify-sync') }}</option>
                </select>
            </div>
            
            @if (!empty($printify_shop_id))
            <div class="mb-3">
                <label class="form-label">{{ __('Current Shop ID', 'wp-woocommerce-printify-sync') }}</label>
                <input type="text" class="form-control" value="{{ $printify_shop_id }}" readonly>
                <small class="form-text text-muted">{{ __('To change the shop, select a new one from the dropdown above after testing the connection.', 'wp-woocommerce-printify-sync') }}</small>
            </div>
            @endif
            
            <button type="submit" class="btn btn-primary">{{ __('Save Settings', 'wp-woocommerce-printify-sync') }}</button>
        </form>
    </div>
</div>
