(function($) {
    'use strict';
    
    const WPWPS = {
        init: function() {
            this.initSidebar();
            this.initTooltips();
            this.initCharts();
            this.initNotifications();
            this.setupAjaxHandlers();
        },
        
        initSidebar: function() {
            const $sidebar = $('#wpwps-sidebar');
            const $toggle = $('#wpwps-toggle-sidebar');
            const $wrapper = $('.wpwps-wrapper');
            
            // Restore sidebar state
            if (localStorage.getItem('wpwps_sidebar_collapsed') === 'true') {
                $wrapper.addClass('wpwps-sidebar-collapsed');
            }
            
            $toggle.on('click', function() {
                $wrapper.toggleClass('wpwps-sidebar-collapsed');
                localStorage.setItem('wpwps_sidebar_collapsed', $wrapper.hasClass('wpwps-sidebar-collapsed'));
            });
        },
        
        initTooltips: function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        },
        
        initCharts: function() {
            // Set default Chart.js options
            if (typeof Chart !== 'undefined') {
                Chart.defaults.font.family = 'Inter, sans-serif';
                Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                Chart.defaults.plugins.tooltip.padding = 12;
                Chart.defaults.plugins.tooltip.cornerRadius = 8;
                Chart.defaults.plugins.legend.labels.usePointStyle = true;
            }
        },
        
        initNotifications: function() {
            $('#wpwps-mark-all-read').on('click', function(e) {
                e.preventDefault();
                
                WPWPS.api.post('mark_notifications_read')
                    .then(function(response) {
                        if (response.success) {
                            $('.wpwps-notifications-badge').remove();
                            $('.wpwps-notifications-body').html(`
                                <div class="wpwps-empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    <p>All caught up!</p>
                                </div>
                            `);
                        }
                    });
            });
        },
        
        setupAjaxHandlers: function() {
            // Add AJAX headers
            $.ajaxSetup({
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpwpsAdmin.nonce);
                }
            });
        },
        
        api: {
            get: function(endpoint, data = {}) {
                return $.ajax({
                    url: wpwpsAdmin.ajaxUrl,
                    method: 'GET',
                    data: {
                        action: 'wpwps_' + endpoint,
                        ...data
                    }
                });
            },
            
            post: function(endpoint, data = {}) {
                return $.ajax({
                    url: wpwpsAdmin.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'wpwps_' + endpoint,
                        _ajax_nonce: wpwpsAdmin.nonce,
                        ...data
                    }
                });
            }
        },
        
        toast: {
            show: function(message, type = 'success') {
                const toast = `
                    <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                        <div class="d-flex">
                            <div class="toast-body">
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                `;
                
                $('.toast-container').append(toast);
                const toastEl = $('.toast').last()[0];
                const bsToast = new bootstrap.Toast(toastEl);
                bsToast.show();
                
                $(toastEl).on('hidden.bs.toast', function() {
                    $(this).remove();
                });
            },
            
            success: function(message) {
                this.show(message, 'success');
            },
            
            error: function(message) {
                this.show(message, 'danger');
            },
            
            warning: function(message) {
                this.show(message, 'warning');
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        WPWPS.init();
    });
    
    // Make WPWPS globally available
    window.WPWPS = WPWPS;
    
})(jQuery);
