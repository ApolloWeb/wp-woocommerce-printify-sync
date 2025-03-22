/**
 * Webhooks page JavaScript.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

jQuery(document).ready(function($) {
    // Generate webhook secret
    $('#generate-webhook-secret').on('click', function() {
        const length = 32;
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        let secret = '';
        
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * chars.length);
            secret += chars.charAt(randomIndex);
        }
        
        $('#printify_webhook_secret').val(secret);
        
        // Show notification
        WPWPS.showToast('New webhook secret generated', 'success');
    });
    
    // Copy webhook URL
    $('.copy-webhook-url').on('click', function() {
        const webhookUrl = $('.webhook-url').text();
        
        // Create temporary textarea to copy from
        const textarea = document.createElement('textarea');
        textarea.value = webhookUrl;
        document.body.appendChild(textarea);
        
        // Select and copy
        textarea.select();
        document.execCommand('copy');
        
        // Remove temporary element
        document.body.removeChild(textarea);
        
        // Show success message
        $('.webhook-copy-success').fadeIn(200).delay(1500).fadeOut(200);
    });
    
    // Test webhook
    $('#test-webhook').on('click', function() {
        const button = $(this);
        const resultContainer = $('#webhook-test-result');
        const webhookSecret = $('#printify_webhook_secret').val();
        
        if (!webhookSecret) {
            resultContainer.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Please generate a webhook secret first.</div>');
            return;
        }
        
        // Show loading state
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        resultContainer.html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Testing webhook configuration...</div>');
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_webhook',
                nonce: wpwps_data.nonce,
                webhook_secret: webhookSecret
            },
            success: function(response) {
                if (response.success) {
                    resultContainer.html('<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + response.data.message + '</div>');
                } else {
                    resultContainer.html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + response.data.message + '</div>');
                }
            },
            error: function() {
                resultContainer.html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Failed to test webhook configuration.</div>');
            },
            complete: function() {
                // Reset button state
                button.prop('disabled', false);
                button.html('<i class="fas fa-vial"></i> Test Webhook');
            }
        });
    });
});
