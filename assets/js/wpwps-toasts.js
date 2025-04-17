/**
 * Toast notification manager
 */
var wpwpsToastManager = (function($) {
    'use strict';
    
    /**
     * Initialize the toast manager
     */
    function init() {
        // Prepare toast container
        if ($('#wpwps-toast-container').length === 0) {
            $('body').append('<div id="wpwps-toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11000;"></div>');
        }
    }
    
    /**
     * Show a toast notification
     * 
     * @param {string} type - Type of toast (success, error, warning, info)
     * @param {string} title - Toast title
     * @param {string} message - Toast message
     * @param {number} duration - Duration in milliseconds
     */
    function showToast(type, title, message, duration) {
        duration = duration || 5000;
        
        // Map type to Bootstrap and icon classes
        var typeMap = {
            'success': { 
                'icon': 'fa-check-circle',
                'border': 'border-success',
                'text': 'text-success'
            },
            'error': { 
                'icon': 'fa-exclamation-circle',
                'border': 'border-danger',
                'text': 'text-danger'
            },
            'warning': { 
                'icon': 'fa-exclamation-triangle',
                'border': 'border-warning',
                'text': 'text-warning'
            },
            'info': { 
                'icon': 'fa-info-circle',
                'border': 'border-info',
                'text': 'text-info'
            }
        };
        
        // Use info as default if type not found
        var typeClass = typeMap[type] || typeMap['info'];
        
        // Create a unique ID for the toast
        var toastId = 'wpwps-toast-' + Date.now();
        
        // Create the toast HTML - using square styling with thick left border
        var toastHtml = 
            '<div id="' + toastId + '" class="toast ' + typeClass.border + ' shadow-sm fade" role="alert" aria-live="assertive" aria-atomic="true">' +
                '<div class="toast-header">' +
                    '<i class="fas ' + typeClass.icon + ' me-2 ' + typeClass.text + '"></i>' +
                    '<strong class="me-auto">' + title + '</strong>' +
                    '<small class="text-muted">just now</small>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>' +
                '</div>' +
                '<div class="toast-body">' + message + '</div>' +
            '</div>';
        
        // Add toast to container
        $('#wpwps-toast-container').append(toastHtml);
        
        // Initialize the toast with Bootstrap
        var toastElement = document.getElementById(toastId);
        var toast = new bootstrap.Toast(toastElement, {
            delay: duration,
            autohide: true
        });
        
        // Add event listener for when toast is hidden to remove it from DOM
        toastElement.addEventListener('hidden.bs.toast', function() {
            $(this).remove();
        });
        
        // Show the toast
        toast.show();
        
        return toastId;
    }
    
    /**
     * Send a toast via AJAX
     */
    function sendToast(type, title, message, duration) {
        $.ajax({
            url: wpwps_toasts.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_add_toast',
                nonce: wpwps_toasts.nonce,
                type: type,
                title: title,
                message: message,
                duration: duration
            }
        });
    }
    
    // Initialize on document ready
    $(document).ready(init);
    
    // Return public methods
    return {
        showToast: showToast,
        sendToast: sendToast
    };
    
})(jQuery);
