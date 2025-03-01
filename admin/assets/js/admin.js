/**
 * Common admin JavaScript for WP WooCommerce Printify Sync
 */
jQuery(document).ready(function($) {
    'use strict';
    
    /**
     * Show message
     *
     * @param {string} message The message text
     * @param {string} type The message type (success, error)
     * @param {object} container The container to show the message in
     */
    function showMessage(message, type, container) {
        container = container || $('#wpwps-settings-message');
        
        container
            .removeClass('notice-success notice-error')
            .addClass('notice-' + type)
            .html('<p>' + message + '</p>')
            .fadeIn();
        
        // Hide message after 5 seconds if it's a success message
        if (type === 'success') {
            setTimeout(function() {
                container.fadeOut();
            }, 5000);
        }
    }
    
    /**
     * Show loading state
     *
     * @param {object} button The button to show loading state for
     * @param {boolean} isLoading Whether to show or hide loading state
     */
    function showLoading(button, isLoading) {
        if (isLoading) {
            button.prop('disabled', true);
            button.siblings('.spinner').addClass('is-active');
        } else {
            button.prop('disabled', false);
            button.siblings('.spinner').removeClass('is-active');
        }
    }
    
    /**
     * Save settings form
     */
    $('#wpwps-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var button = $('#wpwps-save-settings');
        var messageContainer = $('#wpwps-settings-message');
        
        showLoading(button, true);
        
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_save_settings',
                nonce: wpwps.nonce,
                api_key: $('#wpwps-api-key').val()
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success', messageContainer);
                } else {
                    showMessage(response.data.message, 'error', messageContainer);
                }
            },
            error: function() {
                showMessage('An error occurred while saving settings.', 'error', messageContainer);
            },
            complete: function() {
                showLoading(button, false);
            }
        });
    });
    
    // Expose functions for other scripts
    window.wpwps = window.wpwps || {};
    window.wpwps.showMessage = showMessage;
    window.wpwps.showLoading = showLoading;
});