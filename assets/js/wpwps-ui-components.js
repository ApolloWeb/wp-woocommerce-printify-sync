/**
 * UI Components for WP WooCommerce Printify Sync
 *
 * Handles quick actions and toast notifications
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initQuickActions();
        initToastSystem();
    });
    
    /**
     * Initialize Quick Actions functionality
     */
    function initQuickActions() {
        // Initialize the quick actions functionality
        $('.wpwps-action-trigger').on('click', function(e) {
            e.preventDefault();
            const actionTarget = $(this).data('action');
            const actionType = $(this).data('type');
            
            if (actionType === 'url') {
                window.location.href = actionTarget;
            } else {
                // Show loading indicator within the button
                const $button = $(this);
                const originalContent = $button.html();
                $button.html('<span class="wpwps-spinner"></span> ' + $button.text()).prop('disabled', true);
                
                // For AJAX actions with improved error handling
                $.post(ajaxurl, {
                    action: actionTarget,
                    nonce: wpwpsUIComponents.quickActionNonce
                })
                .done(function(response) {
                    if (response.success) {
                        // Show success notification with enhanced styling
                        showToast('Action Complete', response.data.message || 'Action performed successfully', 'success');
                        
                        // Add success animation to page element if specified
                        if (response.data.targetElement) {
                            $(response.data.targetElement).addClass('wpwps-anim-highlight').delay(1500).queue(function() {
                                $(this).removeClass('wpwps-anim-highlight').dequeue();
                            });
                        }
                        
                        // Auto-refresh if needed
                        if (response.data.refresh) {
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    } else {
                        // Show detailed error notification
                        showToast('Action Failed', response.data.message || 'Something went wrong', 'danger');
                    }
                })
                .fail(function(jqXHR) {
                    // Handle network or server errors
                    showToast(
                        'Connection Error', 
                        'Could not connect to the server. Please check your connection and try again.', 
                        'danger'
                    );
                })
                .always(function() {
                    // Restore button state
                    $button.html(originalContent).prop('disabled', false);
                });
            }
        });
        
        // Add keyboard shortcuts for quick access
        $(document).on('keydown', function(e) {
            // Alt+Q to open quick actions menu
            if (e.altKey && e.key === 'q') {
                e.preventDefault();
                $('#wpwps-quick-actions-toggle').click();
            }
            
            // Escape key to close quick actions menu
            if (e.key === 'Escape' && $('#wpwps-quick-actions').is(':visible')) {
                $('#wpwps-quick-actions').removeClass('active');
            }
        });
        
        // Setup quick actions panel toggle
        $('#wpwps-quick-actions-toggle').on('click', function(e) {
            e.preventDefault();
            const $panel = $('#wpwps-quick-actions');
            
            // Toggle with animation
            if ($panel.hasClass('active')) {
                $panel.removeClass('active');
                setTimeout(function() {
                    $panel.addClass('hidden');
                }, 300);
            } else {
                $panel.removeClass('hidden');
                // Slight delay for the animation to work properly
                setTimeout(function() {
                    $panel.addClass('active');
                }, 10);
                
                // Focus the first action button
                setTimeout(function() {
                    $panel.find('.wpwps-action-button:first').focus();
                }, 300);
            }
        });
        
        // Close quick actions panel
        $('.wpwps-quick-actions-close').on('click', function() {
            const $panel = $('#wpwps-quick-actions');
            $panel.removeClass('active');
            setTimeout(function() {
                $panel.addClass('hidden');
            }, 300);
        });
    }
    
    /**
     * Initialize Toast Notification System
     */
    function initToastSystem() {
        // Make sure we have a toast container
        if (!$('#wpwps-toast-container').length) {
            $('body').append('<div id="wpwps-toast-container"></div>');
        }
    }
    
    /**
     * Display a toast notification
     * 
     * @param {string} title Toast title
     * @param {string} message Toast message
     * @param {string} type Toast type (success, info, warning, danger)
     * @returns {jQuery} Toast element
     */
    window.showToast = function(title, message, type = 'info') {
        const icons = {
            'success': 'fa-check-circle',
            'warning': 'fa-exclamation-triangle',
            'danger': 'fa-times-circle',
            'info': 'fa-info-circle'
        };
        
        // Create unique ID for this toast
        const toastId = 'toast-' + Date.now();
        
        // Create toast with improved styling and animations
        const html = `
            <div class="wpwps-toast wpwps-toast-${type}" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="wpwps-toast-header">
                    <div class="wpwps-toast-icon">
                        <i class="fa-solid ${icons[type]}"></i>
                    </div>
                    <div class="wpwps-toast-content">
                        <h4>${title}</h4>
                        <p>${message}</p>
                    </div>
                    <button type="button" class="wpwps-toast-close" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="wpwps-toast-progress"><div class="wpwps-toast-progress-bar"></div></div>
            </div>
        `;
        
        // Add toast to container
        if (!$('#wpwps-toast-container').length) {
            $('body').append('<div id="wpwps-toast-container"></div>');
        }
        
        // Add and position toast
        const $toast = $(html).appendTo('#wpwps-toast-container');
        
        // Position toast based on how many are visible
        const visibleToasts = $('.wpwps-toast:visible').length;
        $toast.css('bottom', (visibleToasts - 1) * 10 + 'px');
        
        // Play sound effect if enabled (only for important notifications)
        if (type === 'danger' || type === 'warning') {
            const audio = new Audio(wpwpsUIComponents.pluginUrl + 'assets/sounds/notification.mp3');
            audio.volume = 0.2;
            const playPromise = audio.play();
            
            // Handle play promise to avoid uncaught promise errors
            if (playPromise !== undefined) {
                playPromise.catch(() => {
                    // Auto-play was prevented, do nothing
                });
            }
        }
        
        // Animate in
        setTimeout(() => {
            $toast.addClass('wpwps-toast-show');
        }, 50);
        
        // Set timeout to dismiss
        const duration = type === 'danger' ? 8000 : 5000;
        
        // Animate progress bar
        $toast.find('.wpwps-toast-progress-bar').css({
            'transition': `width ${duration}ms linear`,
            'width': '0%'
        });
        
        const timeout = setTimeout(() => {
            dismissToast($toast);
        }, duration);
        
        // Close button handler
        $toast.find('.wpwps-toast-close').on('click', function() {
            clearTimeout(timeout);
            dismissToast($toast);
        });
        
        // Hover to pause timer
        $toast.on('mouseenter', function() {
            $toast.find('.wpwps-toast-progress-bar').css('transition', 'none');
            clearTimeout(timeout);
        }).on('mouseleave', function() {
            const remainingTime = parseFloat($toast.find('.wpwps-toast-progress-bar').width()) / 
                                parseFloat($toast.find('.wpwps-toast-progress').width()) * duration;
            
            $toast.find('.wpwps-toast-progress-bar').css({
                'transition': `width ${remainingTime}ms linear`,
                'width': '0%'
            });
            
            timeout = setTimeout(() => {
                dismissToast($toast);
            }, remainingTime);
        });
        
        return $toast;
    };
    
    /**
     * Dismiss a toast notification with animation
     * 
     * @param {jQuery} $toastElement Toast element to dismiss
     */
    function dismissToast($toastElement) {
        $toastElement.removeClass('wpwps-toast-show');
        $toastElement.addClass('wpwps-toast-hide');
        
        setTimeout(() => {
            $toastElement.remove();
            
            // Reposition remaining toasts
            $('.wpwps-toast').each(function(index) {
                $(this).css('bottom', index * 10 + 'px');
            });
        }, 300);
    }
    
})(jQuery);
