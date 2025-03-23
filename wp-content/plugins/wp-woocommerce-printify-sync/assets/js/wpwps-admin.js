(function($) {
    'use strict';

    const WPWPS = {
        init() {
            this.initSidebar();
            this.initTooltips();
        },

        initSidebar() {
            $('.wpwps-sidebar-toggle').on('click', () => {
                $('.wpwps-sidebar').toggleClass('collapsed');
                $('.wpwps-main-content').toggleClass('expanded');
            });
        },

        initTooltips() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        },

        // Enhanced AJAX helper
        api: {
            post(action, data = {}) {
                return $.ajax({
                    url: wppsAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: `wpps_${action}`,
                        _ajax_nonce: wppsAdmin.nonce,
                        ...data
                    }
                }).catch(error => {
                    // Global error handler
                    console.error(`API Error (${action}):`, error);
                    if (error.responseJSON && error.responseJSON.data) {
                        WPWPS.toast.show(error.responseJSON.data.message || wppsAdmin.i18n.error, 'danger');
                    } else {
                        WPWPS.toast.show(wppsAdmin.i18n.connectionError, 'danger');
                    }
                    throw error;
                });
            },
            
            get(action, data = {}) {
                return $.ajax({
                    url: wppsAdmin.ajaxUrl,
                    type: 'GET',
                    data: {
                        action: `wpps_${action}`,
                        _ajax_nonce: wppsAdmin.nonce,
                        ...data
                    }
                }).catch(error => {
                    console.error(`API Error (${action}):`, error);
                    WPWPS.toast.show(wppsAdmin.i18n.error, 'danger');
                    throw error;
                });
            },
            
            batch(requests) {
                return this.post('batch', { requests: JSON.stringify(requests) });
            }
        },

        // Enhanced toast system
        toast: {
            show(message, type = 'success') {
                const toast = `
                    <div class="wpwps-toast position-fixed top-0 end-0 p-3" style="z-index: 1056">
                        <div class="toast align-items-center text-white bg-${type}" role="alert">
                            <div class="d-flex">
                                <div class="toast-body">${message}</div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                                        data-bs-dismiss="toast"></button>
                            </div>
                        </div>
                    </div>`;
                
                $(toast).appendTo('body').find('.toast').toast('show');
            },
            
            success(message) {
                this.show(message, 'success');
            },
            
            error(message) {
                this.show(message || wppsAdmin.i18n.error, 'danger');
            },
            
            warning(message) {
                this.show(message, 'warning');
            },
            
            info(message) {
                this.show(message, 'info');
            }
        },
        
        // Form utilities
        form: {
            serialize(form) {
                const formData = form instanceof jQuery ? form[0] : form;
                return Object.fromEntries(new FormData(formData));
            },
            
            validate(form) {
                const $form = form instanceof jQuery ? form : $(form);
                if (!$form[0].checkValidity()) {
                    $form.addClass('was-validated');
                    return false;
                }
                return true;
            }
        },
        
        // UI utilities
        ui: {
            loading(selector, state = true) {
                const $el = $(selector);
                if (state) {
                    $el.addClass('wpwps-loading').prop('disabled', true);
                    if ($el.is('button') && !$el.find('.spinner-border').length) {
                        $el.prepend('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>');
                    }
                } else {
                    $el.removeClass('wpwps-loading').prop('disabled', false);
                    $el.find('.spinner-border').remove();
                }
            },
            
            confirm(message, callback) {
                if (confirm(message)) {
                    callback();
                }
            }
        }
    };

    // Initialize on DOM ready
    $(document).ready(() => WPWPS.init());

    // Export for other modules
    window.WPWPS = WPWPS;

})(jQuery);
