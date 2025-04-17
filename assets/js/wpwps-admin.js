/**
 * Admin scripts for WP WooCommerce Printify Sync
 */
(function($) {
    'use strict';
    
    // Document ready
    $(function() {
        initializeAdminUI();
    });
    
    /**
     * Initialize the admin UI components
     */
    function initializeAdminUI() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Add example button click handlers for toast notifications
        $('.wpwps-toast-example-success').on('click', function(e) {
            e.preventDefault();
            wpwpsToastManager.showToast(
                'success',
                'Success',
                'Your operation was completed successfully!',
                5000
            );
        });
        
        $('.wpwps-toast-example-error').on('click', function(e) {
            e.preventDefault();
            wpwpsToastManager.showToast(
                'error',
                'Error',
                'Something went wrong. Please try again.',
                5000
            );
        });
        
        $('.wpwps-toast-example-warning').on('click', function(e) {
            e.preventDefault();
            wpwpsToastManager.showToast(
                'warning',
                'Warning',
                'This action might cause issues.',
                5000
            );
        });
        
        $('.wpwps-toast-example-info').on('click', function(e) {
            e.preventDefault();
            wpwpsToastManager.showToast(
                'info',
                'Information',
                'Here is some information you might need.',
                5000
            );
        });
    }
    
})(jQuery);
