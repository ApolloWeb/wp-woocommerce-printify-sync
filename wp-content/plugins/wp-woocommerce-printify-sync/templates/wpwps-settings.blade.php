@extends('layouts.wpwps-main')

@section('title', 'Settings')
@section('page-title', 'Plugin Settings')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="wpwps-card p-4 mb-4">
            <h4 class="mb-4">Printify API Configuration</h4>
            <form id="printifySettingsForm">
                @wp_nonce_field('wpwps_ajax_nonce', 'nonce')
                
                <div class="mb-3">
                    <label for="api_endpoint" class="form-label">API Endpoint</label>
                    <input type="url" 
                           class="form-control" 
                           id="api_endpoint" 
                           name="api_endpoint"
                           value="{{ $api_endpoint }}"
                           placeholder="https://api.printify.com/v1"
                           required>
                    <div class="form-text">The Printify API endpoint URL</div>
                </div>
                
                <div class="mb-3">
                    <label for="api_key" class="form-label">API Key</label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="api_key" 
                               name="api_key"
                               value="{{ $api_key }}"
                               required>
                        <button class="btn btn-outline-secondary" 
                                type="button" 
                                id="toggleApiKey">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Get your API key from Printify Dashboard → Settings → API</div>
                </div>

                <div class="mb-3">
                    <label for="shop_id" class="form-label">Shop Selection</label>
                    <select class="form-select" id="shop_id" name="shop_id" required>
                        <option value="">Select a shop</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop['id'] }}" 
                                    {{ $shop_id == $shop['id'] ? 'selected' : '' }}>
                                {{ $shop['title'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="button" 
                        class="btn btn-outline-primary me-2" 
                        id="testConnection">
                    <i class="fas fa-plug me-2"></i>Test Connection
                </button>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </form>
        </div>

        <div class="wpwps-card p-4 mb-4">
            <h4 class="mb-4">AI Support Configuration</h4>
            <form id="gptSettingsForm">
                <div class="mb-3">
                    <label for="gpt_api_key" class="form-label">ChatGPT API Key</label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="gpt_api_key" 
                               name="gpt_api_key"
                               value="{{ $gpt_api_key }}">
                        <button class="btn btn-outline-secondary" 
                                type="button" 
                                id="toggleGptKey">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="token_limit" class="form-label">Token Limit</label>
                    <input type="number" 
                           class="form-control" 
                           id="token_limit" 
                           name="gpt_settings[token_limit]"
                           value="{{ $gpt_settings['token_limit'] }}"
                           min="1"
                           max="4000">
                </div>

                <div class="mb-3">
                    <label for="temperature" class="form-label">Temperature</label>
                    <input type="range" 
                           class="form-range" 
                           id="temperature" 
                           name="gpt_settings[temperature]"
                           value="{{ $gpt_settings['temperature'] }}"
                           min="0"
                           max="1"
                           step="0.1">
                    <div class="form-text">Current: <span id="temperatureValue">{{ $gpt_settings['temperature'] }}</span></div>
                </div>

                <div class="mb-3">
                    <label for="spend_cap" class="form-label">Monthly Spend Cap (USD)</label>
                    <input type="number" 
                           class="form-control" 
                           id="spend_cap" 
                           name="gpt_settings[spend_cap]"
                           value="{{ $gpt_settings['spend_cap'] }}"
                           min="0">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save AI Settings
                </button>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="wpwps-card p-4 mb-4">
            <h5 class="mb-3">Connection Status</h5>
            <div id="connectionStatus">
                <div class="d-flex align-items-center">
                    <div class="spinner-grow spinner-grow-sm text-primary me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    Checking connection...
                </div>
            </div>
        </div>

        <div class="wpwps-card p-4">
            <h5 class="mb-3">Support</h5>
            <p>Need help? Check out our documentation or contact support:</p>
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-action">
                    <i class="fas fa-book me-2"></i>Documentation
                </a>
                <a href="#" class="list-group-item list-group-item-action">
                    <i class="fas fa-life-ring me-2"></i>Support Center
                </a>
                <a href="#" class="list-group-item list-group-item-action">
                    <i class="fas fa-code me-2"></i>API Reference
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('additional-js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const temperatureInput = document.getElementById('temperature');
    const temperatureValue = document.getElementById('temperatureValue');
    const toggleApiKey = document.getElementById('toggleApiKey');
    const toggleGptKey = document.getElementById('toggleGptKey');
    const testConnectionBtn = document.getElementById('testConnection');
    const apiKeyInput = document.getElementById('api_key');
    const shopIdSelect = document.getElementById('shop_id');

    // Update temperature value display
    temperatureInput?.addEventListener('input', function() {
        temperatureValue.textContent = this.value;
    });

    // Toggle password visibility
    [toggleApiKey, toggleGptKey].forEach(toggle => {
        toggle?.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });

    // Test connection
    testConnectionBtn?.addEventListener('click', async function() {
        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'wpwps_test_connection',
                    nonce: document.querySelector('[name="nonce"]').value,
                    api_key: apiKeyInput.value
                })
            });

            const data = await response.json();
            
            if (data.success) {
                showToast('Connection successful!', 'success');
                // Update shops dropdown
                shopIdSelect.innerHTML = '<option value="">Select a shop</option>' +
                    data.data.shops.map(shop => 
                        `<option value="${shop.id}">${shop.title}</option>`
                    ).join('');
            } else {
                showToast('Connection failed: ' + data.data.message, 'error');
            }
        } catch (error) {
            showToast('Connection failed: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // Handle form submissions
    ['printifySettingsForm', 'gptSettingsForm'].forEach(formId => {
        document.getElementById(formId)?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';

            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'wpwps_save_settings',
                        ...Object.fromEntries(new FormData(form))
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    showToast('Settings saved successfully!', 'success');
                } else {
                    showToast('Failed to save settings: ' + data.data.message, 'error');
                }
            } catch (error) {
                showToast('Failed to save settings: ' + error.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    });
});

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast show mb-2 wpwps-toast ${type}`;
    toast.innerHTML = `
        <div class="toast-header">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;

    const container = document.querySelector('.wpwps-alerts') || createToastContainer();
    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'wpwps-alerts position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1100';
    container.style.marginTop = '4rem';
    document.body.appendChild(container);
    return container;
}
</script>
@endsection