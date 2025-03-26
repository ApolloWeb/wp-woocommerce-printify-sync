document.addEventListener('DOMContentLoaded', function() {
    console.log('WPWPS Admin script initialized');
    
    try {
        // Initialize Charts if we're on the dashboard
        if (document.getElementById('salesChart')) {
            console.log('Initializing dashboard charts');
            initializeDashboardCharts();
        }

        // Initialize Quick Actions
        console.log('Initializing quick actions');
        initializeQuickActions();

        // Load initial data
        console.log('Loading dashboard data');
        loadDashboardData();

        console.log('Initializing other components');
        initializeConnectionTest();
        initializePasswordToggles();
        initializeSettingsForm();
        initializeCostEstimation();
        checkApiHealth();
        initializeMediaUploader();
    } catch (error) {
        console.error('Error during WPWPS initialization:', error);
        document.body.innerHTML = '<div class="wrap"><div class="notice notice-error"><p>Error initializing plugin dashboard. Please check browser console for details.</p></div></div>';
    }
});

function initializeDashboardCharts() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Sales',
                data: [],
                borderColor: '#96588a',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Categories Chart
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    const categoriesChart = new Chart(categoriesCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#96588a',
                    '#4B0082',
                    '#007FFF',
                    '#008080',
                    '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    return { salesChart, categoriesChart };
}

function initializeQuickActions() {
    // Sync Products
    document.getElementById('syncProducts')?.addEventListener('click', function(e) {
        e.preventDefault();
        triggerSync('products');
    });

    // Sync Orders
    document.getElementById('syncOrders')?.addEventListener('click', function(e) {
        e.preventDefault();
        triggerSync('orders');
    });

    // Check API Health
    document.getElementById('checkAPIHealth')?.addEventListener('click', function(e) {
        e.preventDefault();
        checkApiHealth();
    });
}

function loadDashboardData() {
    fetch(wpwps.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'wpwps_get_dashboard_data',
            nonce: wpwps.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateDashboardStats(data.data);
            updateCharts(data.data);
            updateRecentActivity(data.data.activity);
        }
    })
    .catch(error => showToast('Error loading dashboard data', 'error'));
}

function triggerSync(type) {
    showToast(`Starting ${type} sync...`, 'info');
    
    fetch(wpwps.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: `wpwps_sync_${type}`,
            nonce: wpwps.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            loadDashboardData(); // Refresh dashboard data
        } else {
            showToast(data.message || `Error syncing ${type}`, 'error');
        }
    })
    .catch(error => showToast(`Error syncing ${type}`, 'error'));
}

function checkApiHealth() {
    const healthIndicator = document.getElementById('apiHealth');
    const statusText = document.getElementById('apiStatus');
    
    healthIndicator.classList.add('spinner-border');
    statusText.textContent = 'Checking...';
    
    fetch(wpwps.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'wpwps_check_api_health',
            nonce: wpwps.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        healthIndicator.classList.remove('spinner-border');
        if (data.success) {
            healthIndicator.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
            statusText.textContent = 'Healthy';
        } else {
            healthIndicator.innerHTML = '<i class="fas fa-exclamation-circle text-danger"></i>';
            statusText.textContent = 'Issues Detected';
        }
    })
    .catch(error => {
        healthIndicator.classList.remove('spinner-border');
        healthIndicator.innerHTML = '<i class="fas fa-times-circle text-danger"></i>';
        statusText.textContent = 'Connection Error';
    });
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `wpwps-toast ${type}`;
    toast.innerHTML = `
        <div class="toast-header">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            <strong class="me-auto">Printify Sync</strong>
            <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;
    document.body.appendChild(toast);
    
    // Remove toast after 5 seconds
    setTimeout(() => toast.remove(), 5000);
}

function updateDashboardStats(data) {
    if (data.stats) {
        document.getElementById('totalProducts').textContent = data.stats.products || '--';
        document.getElementById('activeOrders').textContent = data.stats.orders || '--';
        document.getElementById('openTickets').textContent = data.stats.tickets || '--';
        
        // Update change indicators
        document.getElementById('productsChange').textContent = data.stats.productsChange || '--';
        document.getElementById('ordersChange').textContent = data.stats.ordersChange || '--';
        document.getElementById('ticketsChange').textContent = data.stats.ticketsChange || '--';
    }
}

function updateCharts(data) {
    const charts = initializeDashboardCharts();
    
    if (data.salesData) {
        charts.salesChart.data.labels = data.salesData.labels;
        charts.salesChart.data.datasets[0].data = data.salesData.values;
        charts.salesChart.update();
    }
    
    if (data.categoryData) {
        charts.categoriesChart.data.labels = data.categoryData.labels;
        charts.categoriesChart.data.datasets[0].data = data.categoryData.values;
        charts.categoriesChart.update();
    }
}

function updateRecentActivity(activity) {
    if (!activity || !activity.length) {
        return;
    }
    
    const tbody = document.getElementById('recentActivity');
    tbody.innerHTML = activity.map(item => `
        <tr>
            <td>${item.time}</td>
            <td><span class="badge bg-${item.type === 'success' ? 'success' : item.type === 'error' ? 'danger' : 'info'}">${item.type}</span></td>
            <td>${item.description}</td>
            <td>${item.status}</td>
        </tr>
    `).join('');
}

function initializePasswordToggles() {
    const toggleButtons = ['toggleApiKey', 'toggleOpenAIKey'];
    
    toggleButtons.forEach(id => {
        const button = document.getElementById(id);
        if (!button) return;
        
        button.addEventListener('click', function() {
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

function initializeSettingsForm() {
    const form = document.getElementById('wpwpsSettingsForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';

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
                if (data.shop_id) {
                    document.getElementById('shopSelector').style.display = 'none';
                    document.getElementById('currentShopId').style.display = 'block';
                    document.querySelector('#currentShopId .form-control').textContent = data.shop_id;
                }
            } else {
                showToast(data.message || 'Save failed', 'error');
            }
        })
        .catch(error => {
            showToast('Save failed', 'error');
            console.error('Settings save error:', error);
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        });
    });
}

function initializeCostEstimation() {
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

function showToast(message, type = 'success') {
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
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'wpwpsToasts';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

function initializeMediaUploader() {
    const uploadButton = document.getElementById('uploadLogo');
    if (!uploadButton) return;

    let mediaUploader;

    uploadButton.addEventListener('click', function(e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select Company Logo',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            document.getElementById('company_logo').value = attachment.url;
        });

        mediaUploader.open();
    });
}