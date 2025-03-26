document.addEventListener('DOMContentLoaded', function() {
    initializeApiSettings();
    initializePasswordToggles();
    initializeLogoUploader();
    initializeFormSubmission();
    initializeCostEstimator();
    initializeRateLimitSettings();

    // Initialize form submission
    const settingsForm = document.getElementById('wpwpsSettingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(wpwps.ajax_url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Settings saved successfully', 'success');
                } else {
                    showToast(data.data || 'Error saving settings', 'error');
                }
            })
            .catch(error => {
                console.error('Settings save error:', error);
                showToast('Error saving settings', 'error');
            });
        });
    }
});

function initializeApiSettings() {
    const testButton = document.getElementById('testConnection');
    const shopSelector = document.getElementById('shopSelector');
    const currentShopId = document.getElementById('currentShopId');
    const apiStatus = document.getElementById('apiStatus');
    const apiHealth = document.getElementById('apiHealth');

    if (!testButton) return;

    testButton.addEventListener('click', function() {
        const apiKey = document.getElementById('api_key').value;
        const endpoint = document.getElementById('endpoint').value;

        if (!apiKey || !endpoint) {
            showToast('Please enter API key and endpoint', 'error');
            return;
        }

        // Show loading state
        testButton.disabled = true;
        testButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Testing...';

        const formData = new FormData();
        formData.append('action', 'wpwps_test_connection');
        formData.append('nonce', wpwps.nonce);
        formData.append('api_key', apiKey);
        formData.append('endpoint', endpoint);

        fetch(wpwps.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                if (data.shops) {
                    populateShopSelector(data.shops);
                    shopSelector.style.display = 'block';
                    currentShopId.style.display = 'none';
                }
                apiStatus.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i>Connected';
                apiHealth.classList.remove('spinner-border');
            } else {
                showToast(data.message || 'Connection failed', 'error');
                apiStatus.innerHTML = '<i class="fas fa-exclamation-circle text-danger me-2"></i>Not Connected';
                apiHealth.classList.remove('spinner-border');
            }
        })
        .catch(error => {
            showToast('Connection failed', 'error');
            console.error('API test error:', error);
            apiStatus.innerHTML = '<i class="fas fa-exclamation-circle text-danger me-2"></i>Error';
            apiHealth.classList.remove('spinner-border');
        })
        .finally(() => {
            testButton.disabled = false;
            testButton.innerHTML = '<i class="fas fa-plug me-1"></i> Test Connection';
        });
    });

    function updateApiStatus() {
        const statusDiv = document.getElementById('apiLimitStatus');
        if (!statusDiv) return;

        fetch(wpwps.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'wpwps_get_api_health',
                nonce: wpwps.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const { stats, rate_limit } = data;
                let statusHtml = '<div class="d-flex flex-column">';
                
                // Rate limit status
                if (rate_limit.remaining !== null) {
                    const statusClass = rate_limit.remaining > 100 ? 'text-success' : 
                                     (rate_limit.remaining > 20 ? 'text-warning' : 'text-danger');
                    statusHtml += `
                        <div class="mb-2">
                            <strong>Rate Limit:</strong> 
                            <span class="${statusClass}">
                                ${rate_limit.remaining} requests remaining
                            </span>
                        </div>`;
                }

                // API usage stats
                statusHtml += `
                    <div class="mb-2">
                        <strong>24h Usage:</strong> ${stats.last_24h} calls
                    </div>
                    <div class="mb-2">
                        <strong>Error Rate:</strong> 
                        <span class="${stats.errors > 10 ? 'text-danger' : 'text-success'}">
                            ${((stats.errors / stats.total) * 100).toFixed(1)}%
                        </span>
                    </div>`;

                statusHtml += '</div>';
                statusDiv.innerHTML = statusHtml;
            } else {
                statusDiv.innerHTML = `
                    <div class="text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Failed to fetch API status
                    </div>`;
            }
        })
        .catch(error => {
            console.error('API health check error:', error);
            statusDiv.innerHTML = `
                <div class="text-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error checking API status
                </div>`;
        });
    }

    // Update API status every minute
    updateApiStatus();
    setInterval(updateApiStatus, 60000);
}

function populateShopSelector(shops) {
    const select = document.getElementById('shop_id');
    if (!select) return;

    select.innerHTML = '<option value="">' + 
        wpwps.i18n.select_shop + 
        '</option>';

    shops.forEach(shop => {
        const option = document.createElement('option');
        option.value = shop.id;
        option.textContent = shop.title + ' (' + shop.id + ')';
        select.appendChild(option);
    });
}

function initializePasswordToggles() {
    const toggles = document.querySelectorAll('[id^="toggle"]');
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

function initializeLogoUploader() {
    const uploadButton = document.getElementById('uploadLogo');
    if (!uploadButton) return;

    uploadButton.addEventListener('click', function(e) {
        e.preventDefault();
        
        const customUploader = wp.media({
            title: 'Select Company Logo',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        customUploader.on('select', function() {
            const attachment = customUploader.state().get('selection').first().toJSON();
            document.getElementById('company_logo').value = attachment.url;
        });

        customUploader.open();
    });
}

function initializeFormSubmission() {
    const form = document.getElementById('wpwpsSettingsForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'wpwps_save_settings');
        formData.append('nonce', wpwps.nonce);

        fetch(wpwps.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Failed to save settings', 'error');
            }
        })
        .catch(error => {
            showToast('Error saving settings', 'error');
            console.error('Settings save error:', error);
        });
    });
}

function initializeCostEstimator() {
    const estimateButton = document.getElementById('estimateCost');
    if (!estimateButton) return;

    estimateButton.addEventListener('click', function() {
        const maxTokens = document.getElementById('openai_max_tokens').value;
        const monthlyCap = document.getElementById('openai_monthly_cap').value;

        if (!maxTokens || !monthlyCap) {
            showToast('Please enter max tokens and monthly cap', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'wpwps_estimate_gpt_cost');
        formData.append('nonce', wpwps.nonce);
        formData.append('max_tokens', maxTokens);
        formData.append('monthly_cap', monthlyCap);

        fetch(wpwps.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const estimateDiv = document.getElementById('costEstimate');
                estimateDiv.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Estimated Usage:</strong><br>
                        Maximum requests per month: ${data.estimated_requests}<br>
                        Cost per request: $${data.cost_per_request.toFixed(4)}
                    </div>
                `;
                estimateDiv.style.display = 'block';
            } else {
                showToast(data.message || 'Estimation failed', 'error');
            }
        })
        .catch(error => {
            showToast('Estimation failed', 'error');
            console.error('Cost estimation error:', error);
        });
    });
}

function initializeRateLimitSettings() {
    const form = document.getElementById('wpwpsSettingsForm');
    const maxRetries = document.getElementById('max_retries');
    const retryDelay = document.getElementById('retry_delay');
    const rateBuffer = document.getElementById('rate_limit_buffer');

    const updateRateLimitPreview = () => {
        const attempts = parseInt(maxRetries.value);
        const delay = parseInt(retryDelay.value);
        let totalTime = 0;
        let currentDelay = delay;

        for (let i = 1; i < attempts; i++) {
            totalTime += currentDelay;
            currentDelay *= 2;
        }

        document.getElementById('retryPreview')?.remove();
        
        const preview = document.createElement('div');
        preview.id = 'retryPreview';
        preview.className = 'alert alert-info mt-3';
        preview.innerHTML = `
            <i class="fas fa-info-circle me-2"></i>
            <strong>Retry Pattern:</strong><br>
            Maximum total retry time: ${totalTime} seconds<br>
            Retry delays: ${Array.from({length: attempts - 1}, (_, i) => delay * Math.pow(2, i)).join(', ')} seconds
        `;

        rateBuffer.closest('.col-md-4').after(preview);
    };

    [maxRetries, retryDelay].forEach(input => {
        input?.addEventListener('change', updateRateLimitPreview);
    });

    updateRateLimitPreview();
}

function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('wpwpsToasts') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    new bootstrap.Toast(toast, { delay: 3000 }).show();
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'wpwpsToasts';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}