/**
 * Common JavaScript functionality for Printify Sync plugin
 */

(function($) {
    'use strict';

    // Toast notification system
    const wpwpsToast = {
        container: null,
        
        init: function() {
            if (!this.container) {
                this.container = $('<div class="wpwps-toast-container"></div>');
                $('body').append(this.container);
            }
        },
        
        show: function(options) {
            this.init();
            
            const defaults = {
                type: 'info', // info, success, warning, error
                title: '',
                message: '',
                duration: 5000, // milliseconds
                dismissible: true
            };
            
            const settings = $.extend({}, defaults, options);
            
            // Create toast element
            const toast = $('<div class="wpwps-toast wpwps-toast-' + settings.type + '"></div>');
            
            // Add icon based on type
            let icon = 'info-circle';
            switch (settings.type) {
                case 'success':
                    icon = 'check-circle';
                    break;
                case 'warning':
                    icon = 'exclamation-triangle';
                    break;
                case 'error':
                    icon = 'times-circle';
                    break;
            }
            
            toast.append('<div class="wpwps-toast-icon"><i class="fas fa-' + icon + '"></i></div>');
            
            // Add content
            const content = $('<div class="wpwps-toast-content"></div>');
            if (settings.title) {
                content.append('<div class="wpwps-toast-title">' + settings.title + '</div>');
            }
            if (settings.message) {
                content.append('<div class="wpwps-toast-message">' + settings.message + '</div>');
            }
            toast.append(content);
            
            // Add close button if dismissible
            if (settings.dismissible) {
                const closeBtn = $('<div class="wpwps-toast-close"><i class="fas fa-times"></i></div>');
                closeBtn.on('click', function() {
                    toast.remove();
                });
                toast.append(closeBtn);
            }
            
            // Add toast to container
            this.container.append(toast);
            
            // Auto dismiss after duration
            if (settings.duration > 0) {
                setTimeout(function() {
                    toast.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, settings.duration);
            }
            
            return toast;
        },
        
        success: function(message, title = 'Success', options = {}) {
            return this.show($.extend({}, {
                type: 'success',
                title: title,
                message: message
            }, options));
        },
        
        error: function(message, title = 'Error', options = {}) {
            return this.show($.extend({}, {
                type: 'error',
                title: title,
                message: message
            }, options));
        },
        
        warning: function(message, title = 'Warning', options = {}) {
            return this.show($.extend({}, {
                type: 'warning',
                title: title,
                message: message
            }, options));
        },
        
        info: function(message, title = 'Information', options = {}) {
            return this.show($.extend({}, {
                type: 'info',
                title: title,
                message: message
            }, options));
        }
    };
    
    // Make toast system available globally
    window.wpwpsToast = wpwpsToast;
    
    // Add navbar to dashboard
    function addNavbar() {
        if ($('.wpwps-dashboard').length) {
            const navbar = $(`
                <div class="wpwps-navbar-dashboard">
                    <div class="wpwps-navbar-left">
                        <div class="wpwps-navbar-brand">
                            <i class="fas fa-tag"></i> Printify Sync
                        </div>
                        <div class="wpwps-search-bar">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search..." id="wpwps-search">
                        </div>
                    </div>
                    <div class="wpwps-navbar-right">
                        <button class="wpwps-dark-mode-toggle" title="Toggle Dark Mode">
                            <i class="fas fa-moon"></i>
                        </button>
                        <div class="wpwps-navbar-item" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <span class="wpwps-notification-badge">3</span>
                        </div>
                        <div class="wpwps-user-menu">
                            <div class="wpwps-user-avatar">
                                AW
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('.wpwps-dashboard > h1').after(navbar);
        }
    }
    
    // Add quick action buttons
    function addQuickActions() {
        if ($('.wpwps-dashboard').length) {
            const quickActions = $(`
                <div class="wpwps-quick-actions">
                    <div class="wpwps-action-button" title="Sync Now">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                </div>
            `);
            
            $('.wpwps-dashboard').append(quickActions);
            
            // Add click event
            $('.wpwps-action-button').on('click', function() {
                $('#wpwps-sync-all-products').trigger('click');
                wpwpsToast.info('Starting product sync...', 'Sync Initiated');
            });
        }
    }
    
    // Password toggle visibility
    function setupPasswordToggles() {
        $('.toggle-password').on('click', function() {
            const target = $(this).data('toggle');
            const input = $('#' + target);
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    }
    
    // Copy to clipboard functionality
    function setupCopyButtons() {
        $('#wpwps-copy-webhook-url').on('click', function() {
            const webhookUrl = $('#webhook_url').val();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(webhookUrl).then(function() {
                    wpwpsToast.success('Webhook URL copied to clipboard', 'Copied!');
                }, function() {
                    wpwpsToast.error('Failed to copy URL', 'Error');
                });
            } else {
                // Fallback for older browsers
                const textarea = $('<textarea>').val(webhookUrl).appendTo('body').select();
                try {
                    document.execCommand('copy');
                    wpwpsToast.success('Webhook URL copied to clipboard', 'Copied!');
                } catch (err) {
                    wpwpsToast.error('Failed to copy URL', 'Error');
                }
                textarea.remove();
            }
        });
    }
    
    // Handle POP3 settings visibility toggle
    function setupPOP3Toggle() {
        $('#enable_pop3').on('change', function() {
            if ($(this).is(':checked')) {
                $('#pop3-settings').removeClass('d-none');
            } else {
                $('#pop3-settings').addClass('d-none');
            }
        });
    }
    
    // Display temperature value
    function setupTemperatureRange() {
        $('#openai_temperature').on('input', function() {
            $('#temperature-value').text($(this).val());
        });
    }
    
    // Dark mode toggle
    function setupDarkModeToggle() {
        $('.wpwps-dark-mode-toggle').on('click', function() {
            // This is just a placeholder for now
            // In a real implementation, we'd toggle dark mode classes and store the preference
            wpwpsToast.info('Dark mode is coming in a future update!', 'Coming Soon');
        });
    }
    
    // Initialize all common functionality
    $(document).ready(function() {
        // Initialize toast system
        wpwpsToast.init();
        
        // Add navbar to dashboard
        addNavbar();
        
        // Add quick action buttons
        addQuickActions();
        
        // Setup password toggles
        setupPasswordToggles();
        
        // Setup copy buttons
        setupCopyButtons();
        
        // Setup POP3 toggle
        setupPOP3Toggle();
        
        // Setup temperature range display
        setupTemperatureRange();
        
        // Setup dark mode toggle
        setupDarkModeToggle();
        
        // Simulate loading state on buttons
        $('button.button').on('click', function() {
            const $btn = $(this);
            if (!$btn.hasClass('no-loading') && !$btn.find('.wpwps-spinner').length) {
                const originalText = $btn.html();
                $btn.prop('disabled', true);
                
                $btn.prepend('<span class="wpwps-spinner"></span>');
                
                // Simulate loading state for demo purposes
                // In a real implementation, this would be handled by the AJAX success/error callbacks
                setTimeout(function() {
                    $btn.find('.wpwps-spinner').remove();
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                }, 1500);
            }
        });
    });

})(jQuery);
