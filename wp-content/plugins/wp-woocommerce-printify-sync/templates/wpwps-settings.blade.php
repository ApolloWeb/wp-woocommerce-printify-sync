<!-- App Container -->
<div class="wpwps-app">
    <!-- Navigation -->
    @include('partials.shared.navigation')

    <div class="wrap">
        <h1>{{ __('Printify Sync Settings', 'wp-woocommerce-printify-sync') }}</h1>

        <div class="row mt-4">
            <!-- Printify Settings -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">{{ __('Printify API Settings', 'wp-woocommerce-printify-sync') }}</h5>
                    </div>
                    <div class="card-body">
                        <form id="wpwps-printify-settings">
                            <div class="mb-3">
                                <label for="printify_api_key" class="form-label">{{ __('API Key', 'wp-woocommerce-printify-sync') }}</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="printify_api_key" name="printify_api_key" value="{{ $printify_api_key }}" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-api-key">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    {{ __('Get your API key from', 'wp-woocommerce-printify-sync') }} 
                                    <a href="https://printify.com/app/account" target="_blank">Printify Account Settings <i class="fas fa-external-link-alt"></i></a>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="printify_api_endpoint" class="form-label">{{ __('API Endpoint', 'wp-woocommerce-printify-sync') }}</label>
                                <input type="url" class="form-control" id="printify_api_endpoint" name="printify_api_endpoint" value="{{ $printify_api_endpoint }}" required>
                                <div class="form-text">{{ __('Default: https://api.printify.com/v1', 'wp-woocommerce-printify-sync') }}</div>
                            </div>

                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" id="test-printify-connection">
                                    <i class="fas fa-plug me-2"></i>{{ __('Test Connection', 'wp-woocommerce-printify-sync') }}
                                </button>
                            </div>

                            <!-- Connection Test Results -->
                            <div id="connection-results" class="mb-3" style="display: none;">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-muted">{{ __('Connection Test Results', 'wp-woocommerce-printify-sync') }}</h6>
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">{{ __('Profile Name:', 'wp-woocommerce-printify-sync') }}</dt>
                                            <dd class="col-sm-8" id="profile-name"></dd>
                                            <dt class="col-sm-4">{{ __('Email:', 'wp-woocommerce-printify-sync') }}</dt>
                                            <dd class="col-sm-8" id="profile-email"></dd>
                                            <dt class="col-sm-4">{{ __('Shops:', 'wp-woocommerce-printify-sync') }}</dt>
                                            <dd class="col-sm-8" id="shops-count"></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3" id="shop-selector" style="display: none;">
                                <label for="printify_shop_id" class="form-label">{{ __('Select Shop', 'wp-woocommerce-printify-sync') }}</label>
                                <select class="form-select" id="printify_shop_id" name="printify_shop_id" required>
                                    <option value="">{{ __('Select a shop...', 'wp-woocommerce-printify-sync') }}</option>
                                </select>
                            </div>

                            @if ($printify_shop_id && $shop_details)
                            <div class="alert alert-info">
                                <h6 class="alert-heading mb-2">{{ __('Current Shop Details', 'wp-woocommerce-printify-sync') }}</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">{{ __('Shop ID:', 'wp-woocommerce-printify-sync') }}</dt>
                                    <dd class="col-sm-8">{{ $printify_shop_id }}</dd>
                                    <dt class="col-sm-4">{{ __('Title:', 'wp-woocommerce-printify-sync') }}</dt>
                                    <dd class="col-sm-8">{{ $shop_details['title'] }}</dd>
                                </dl>
                            </div>
                            @endif

                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>{{ __('Save Settings', 'wp-woocommerce-printify-sync') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ChatGPT Settings -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">{{ __('ChatGPT Settings', 'wp-woocommerce-printify-sync') }}</h5>
                    </div>
                    <div class="card-body">
                        <form id="wpwps-chatgpt-settings">
                            <div class="mb-3">
                                <label for="chatgpt_api_key" class="form-label">{{ __('API Key', 'wp-woocommerce-printify-sync') }}</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="chatgpt_api_key" name="chatgpt_api_key" value="{{ $chatgpt_api_key }}" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-chatgpt-key">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    {{ __('Get your API key from', 'wp-woocommerce-printify-sync') }}
                                    <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI API Keys <i class="fas fa-external-link-alt"></i></a>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="monthly_cap" class="form-label">{{ __('Monthly Spend Cap ($)', 'wp-woocommerce-printify-sync') }}</label>
                                <input type="number" class="form-control" id="monthly_cap" name="monthly_cap" value="{{ $chatgpt_settings['monthly_cap'] }}" min="1" step="1" required>
                                <div class="form-text">{{ __('Maximum amount to spend per month on API calls', 'wp-woocommerce-printify-sync') }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="tokens" class="form-label">{{ __('Max Tokens per Request', 'wp-woocommerce-printify-sync') }}</label>
                                <input type="number" class="form-control" id="tokens" name="tokens" value="{{ $chatgpt_settings['tokens'] }}" min="1" max="4096" required>
                                <div class="form-text">{{ __('Maximum number of tokens to use per API request (1-4096)', 'wp-woocommerce-printify-sync') }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="temperature" class="form-label">{{ __('Temperature', 'wp-woocommerce-printify-sync') }}</label>
                                <input type="range" class="form-range" id="temperature" name="temperature" value="{{ $chatgpt_settings['temperature'] }}" min="0" max="1" step="0.1">
                                <div class="d-flex justify-content-between">
                                    <small>{{ __('More Focused (0)', 'wp-woocommerce-printify-sync') }}</small>
                                    <small id="temperature-value">{{ $chatgpt_settings['temperature'] }}</small>
                                    <small>{{ __('More Creative (1)', 'wp-woocommerce-printify-sync') }}</small>
                                </div>
                                <div class="form-text">{{ __('Controls randomness in the AI responses', 'wp-woocommerce-printify-sync') }}</div>
                            </div>

                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" id="test-chatgpt">
                                    <i class="fas fa-calculator me-2"></i>{{ __('Calculate Monthly Estimate', 'wp-woocommerce-printify-sync') }}
                                </button>
                            </div>

                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>{{ __('Save Settings', 'wp-woocommerce-printify-sync') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
@include('partials.shared.toast')