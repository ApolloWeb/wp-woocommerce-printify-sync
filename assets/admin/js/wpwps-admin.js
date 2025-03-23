(function($) {
    'use strict';

    const WPWPS = {
        init() {
            this.initSidebar();
            this.initTooltips();
            this.setupAjaxHandlers();
        },

        initSidebar() {
            $('.wpwps-sidebar-toggle').on('click', () => {
                $('.wpwps-sidebar').toggleClass('collapsed');
                $('.wpwps-main-content').toggleClass('expanded');
                
                // Store state in localStorage
                localStorage.setItem('wpwps_sidebar_collapsed', 
                    $('.wpwps-sidebar').hasClass('collapsed') ? '1' : '0');
            });
            
            // Restore sidebar state
            if (localStorage.getItem('wpwps_sidebar_collapsed') === '1') {
                $('.wpwps-sidebar').addClass('collapsed');
                $('.wpwps-main-content').addClass('expanded');
            }
        },

        initTooltips() {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));
        },
        
        setupAjaxHandlers() {
            // Add global AJAX error handler
            $(document).ajaxError((event, jqxhr, settings, thrownError) => {
                if (jqxhr.status === 403) {
                    this.toast.show(wppsAdmin.i18n.permissionDenied, 'danger');
                } else if (jqxhr.status === 0) {
                    this.toast.show(wppsAdmin.i18n.connectionError, 'danger');
                }
            });
        },

        // AJAX helper
        api: {
            post(action, data = {}) {
                return $.ajax({
                    url: wppsAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: `wpwps_${action}`,
                        _ajax_nonce: wppsAdmin.nonce,
                        ...data
                    }
                });
            },
            
            get(action, data = {}) {
                return $.ajax({
                    url: wppsAdmin.ajaxUrl,
                    type: 'GET',
                    data: {
                        action: `wpwps_${action}`,
                        _ajax_nonce: wppsAdmin.nonce,
                        ...data
                    }
                });
            }
        },

        // Toast notifications
        toast: {
            show(message, type = 'success') {
                const toast = `
                    <div class="wpwps-toast position-fixed top-0 end-0 p-3" style="z-index: 1056">
                        <div class="toast align-items-center text-white bg-${type}" role="alert">
                            <div class="d-flex">
                                <div class="toast-body">
                                    ${message}
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                            </div>
                        </div>
                    </div>`;
                
                $(toast).appendTo('body').find('.toast').toast('show');
            },
            
            error(message) {
                this.show(message, 'danger');
            },
            
            success(message) {
                this.show(message, 'success');
            },
            
            info(message) {
                this.show(message, 'info');
            }
        },
        
        // Form utilities
        form: {
            serialize(form) {
                return Array.from(new FormData(form))
                    .reduce((data, [key, value]) => {
                        data[key] = value;
                        return data;
                    }, {});
            }
        }
    };

    // Initialize on DOM ready
    $(document).ready(() => WPWPS.init());

    // Export for other modules
    window.WPWPS = WPWPS;

})(jQuery);
