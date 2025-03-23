/**
 * WP WooCommerce Printify Sync - Admin JS
 */
(function($) {
    'use strict';

    const WPWPS = {
        init: function() {
            this.initSidebar();
            this.initTooltips();
            this.setupAjaxHandlers();
        },

        initSidebar: function() {
            $('.wpwps-sidebar-toggle').on('click', function() {
                $('.wpwps-admin-wrapper').toggleClass('sidebar-collapsed');
                
                // Store state in localStorage
                localStorage.setItem('wpwps_sidebar_collapsed', 
                    $('.wpwps-admin-wrapper').hasClass('sidebar-collapsed') ? '1' : '0');
            });
            
            // Restore sidebar state
            if (localStorage.getItem('wpwps_sidebar_collapsed') === '1') {
                $('.wpwps-admin-wrapper').addClass('sidebar-collapsed');
            }
        },

        initTooltips: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        },
        
        setupAjaxHandlers: function() {
            // Add AJAX setup with global handlers
            $.ajaxSetup({
                beforeSend: function(xhr) {
                    // Add nonce header for all internal AJAX requests
                    if (this.url.indexOf(wpwpsAdmin.ajaxUrl) !== -1) {
                        xhr.setRequestHeader('X-WP-Nonce', wpwpsAdmin.nonce);
                    }
                }
            });
            
            // Add global error handler
            $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                // Don't show errors for silent calls
                if (settings.silentError) {
                    return;
                }
                
                let message = wpwpsAdmin.i18n.error;
                
                if (jqxhr.responseJSON && jqxhr.responseJSON.data && jqxhr.responseJSON.data.message) {
                    message = jqxhr.responseJSON.data.message;
                } else if (thrownError) {
                    message = thrownError;
                }
                
                WPWPS.toast.error(message);
            });
        },

        api: {
            post: function(action, data = {}, options = {}) {
                const ajaxData = {
                    action: 'wpwps_' + action,
                    _ajax_nonce: wpwpsAdmin.nonce,
                    ...data
                };
                
                return $.ajax({
                    url: wpwpsAdmin.ajaxUrl,
                    type: 'POST',
                    data: ajaxData,
                    ...options
                });
            },
            
            get: function(action, data = {}, options = {}) {
                const ajaxData = {
                    action: 'wpwps_' + action,
                    _ajax_nonce: wpwpsAdmin.nonce,
                    ...data
                };
                
                return $.ajax({
                    url: wpwpsAdmin.ajaxUrl,
                    type: 'GET',
                    data: ajaxData,
                    ...options
                });
            }
        },

        toast: {
            show: function(message, type = 'success') {
                const toastHtml = `
                    <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                
                const toastContainer = $('.toast-container');
                
                if (toastContainer.length === 0) {
                    $('body').append('<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 11000;"></div>');
                }
                
                const toast = $(toastHtml);
                $('.toast-container').append(toast);
                
                const bsToast = new bootstrap.Toast(toast[0], {
                    autohide: true,
                    delay: 5000
                });
                
                bsToast.show();
                
                // Auto-remove toast from DOM after it's hidden
                toast.on('hidden.bs.toast', function() {
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
            },
            
            info: function(message) {
                this.show(message, 'info');
            }
        },
        
        loaders: {
            showPageLoader: function() {
                if ($('.wpwps-page-loader').length === 0) {
                    $('body').append(`
                        <div class="wpwps-page-loader">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">${wpwpsAdmin.i18n.loading}</span>
                            </div>
                        </div>
                    `);
                }
            },
            
            hidePageLoader: function() {
                $('.wpwps-page-loader').remove();
            },
            
            showInlineLoader: function(target) {
                const $target = $(target);
                
                if ($target.find('.wpwps-inline-loader').length === 0) {
                    const originalContent = $target.html();
                    $target.data('original-content', originalContent);
                    
                    $target.html(`
                        <span class="wpwps-inline-loader">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <span class="ms-2">${wpwpsAdmin.i18n.loading}</span>
                        </span>
                    `);
                }
                
                $target.attr('disabled', 'disabled');
            },
            
            hideInlineLoader: function(target) {
                const $target = $(target);
                const originalContent = $target.data('original-content');
                
                if (originalContent) {
                    $target.html(originalContent);
                } else {
                    $target.find('.wpwps-inline-loader').remove();
                }
                
                $target.removeAttr('disabled');
            }
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        WPWPS.init();
    });

    // Make WPWPS available globally
    window.WPWPS = WPWPS;
    
})(jQuery);
