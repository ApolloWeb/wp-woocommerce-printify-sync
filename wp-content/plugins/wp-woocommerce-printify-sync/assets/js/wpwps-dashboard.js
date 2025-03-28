/**
 * WP WooCommerce Printify Sync Dashboard Scripts
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Toast notifications
    function showToast(title, message, type = 'success') {
        const toastContainer = document.getElementById('wpwps-toast-container');
        if (!toastContainer) return;

        const iconClass = {
            'success': 'fa-check-circle',
            'danger': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };

        const bgClass = {
            'success': 'bg-success',
            'danger': 'bg-danger',
            'warning': 'bg-warning',
            'info': 'bg-info'
        };

        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.className = 'wpwps-toast toast';
        toast.id = toastId;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="toast-header">
                <i class="fas ${iconClass[type]} me-2 text-${type}"></i>
                <strong class="me-auto">${title}</strong>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 5000
        });
        bsToast.show();
        
        // Remove toast from DOM after it's hidden
        toast.addEventListener('hidden.bs.toast', function () {
            toast.remove();
        });
    }

    // Make showToast function available globally
    window.wpwpsShowToast = showToast;

    // Button click animations
    const animateButtons = document.querySelectorAll('.wpwps-btn');
    animateButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.classList.add('animate__animated', 'animate__pulse');
            setTimeout(() => {
                this.classList.remove('animate__animated', 'animate__pulse');
            }, 1000);
        });
    });

    // Card hover effects
    const cards = document.querySelectorAll('.wpwps-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Chart animations (if charts exist on the page)
    function initCharts() {
        // Check if Chart.js is loaded and charts exist
        if (typeof Chart !== 'undefined') {
            // Set default Chart.js options for consistent styling
            Chart.defaults.font.family = "'Inter', 'Helvetica', sans-serif";
            Chart.defaults.color = "#0f1a20";
            Chart.defaults.plugins.tooltip.backgroundColor = "rgba(15, 26, 32, 0.8)";
            Chart.defaults.animation.duration = 2000;
            Chart.defaults.animation.easing = 'easeOutQuart';
            
            // Custom chart rendering can be added here
        }
    }

    // Initialize charts
    initCharts();

    // API Test buttons
    const testPrintifyBtn = document.getElementById('test-printify');
    const testOpenAIBtn = document.getElementById('test-openai');

    if (testPrintifyBtn) {
        testPrintifyBtn.addEventListener('click', async function() {
            const button = this;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Testing...';

            try {
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
                    showToast('Success!', data.data.message, 'success');
                    
                    // If shops data is available, populate shop dropdown
                    if (data.data.shops && data.data.shops.length > 0) {
                        const shopSelect = document.getElementById('wpwps_printify_shop_id');
                        if (shopSelect) {
                            shopSelect.innerHTML = '';
                            data.data.shops.forEach(shop => {
                                const option = document.createElement('option');
                                option.value = shop.id;
                                option.textContent = shop.title;
                                shopSelect.appendChild(option);
                            });
                        }
                    }
                } else {
                    showToast('Error', data.data.message, 'danger');
                }
            } catch (error) {
                showToast('Error', 'Connection failed. Please try again.', 'danger');
            } finally {
                button.disabled = false;
                button.innerHTML = 'Test Connection';
            }
        });
    }

    if (testOpenAIBtn) {
        testOpenAIBtn.addEventListener('click', async function() {
            const button = this;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Testing...';

            try {
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
                    showToast('Success!', data.data.message, 'success');
                } else {
                    showToast('Error', data.data.message, 'danger');
                }
            } catch (error) {
                showToast('Error', 'Connection failed. Please try again.', 'danger');
            } finally {
                button.disabled = false;
                button.innerHTML = 'Test Connection';
            }
        });
    }
});