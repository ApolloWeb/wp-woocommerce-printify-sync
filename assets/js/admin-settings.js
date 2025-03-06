/**
 * Admin Settings JavaScript
 *
 * Handles all admin settings functionality.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // DOM ready
    $(function() {
        // Test API connection
        $('#test_api_connection').on('click', function(e) {
            e.preventDefault();
            
            const apiToken = $('#printify_api_token').val();
            const resultContainer = $('#api_connection_result');
            
            if (!apiToken) {
                resultContainer.html('<div class="notice notice-error inline"><p>Please enter an API token.</p></div>');
                return;
            }
            
            // Show loading message
            resultContainer.html('<div class="notice notice-info inline"><p>' + wpwprintifysyncSettings.i18n.testing + '</p></div>');
            
            // Make AJAX request
            $.ajax({
                url: wpwprintifysyncSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wpwprintifysync_test_api_connection',
                    nonce: wpwprintifysyncSettings.nonce,
                    api_token: apiToken
                },
                success: function(response) {
                    if (response.success) {
                        // Success - show shops dropdown
                        resultContainer.html('<div class="notice notice-success inline"><p>' + wpwprintifysyncSettings.i18n.testSuccess + '</p></div>');
                        
                        // Update shops dropdown
                        if (response.data.shops) {
                            const shopSelect = $('#printify_shop_id');
                            shopSelect.empty();
                            shopSelect.append('<option value="">' + '-- Select Shop --' + '</option>');
                            
                            $.each(response.data.shops, function(id, name) {
                                shopSelect.append('<option value="' + id + '">' + name + '</option>');
                            });
                        }
                    } else {
                        // Error
                        resultContainer.html('<div class="notice notice-error inline"><p>' + wpwprintifysyncSettings.i18n.testFailed + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    // AJAX error
                    resultContainer.html('<div class="notice notice-error inline"><p>Server error. Please try again.</p></div>');
                }
            });
        });
        
        // Register webhook
        $('#register_webhook').on('click', function(e) {
            e.preventDefault();
            
            const shopId = $('#printify_shop_id').val();
            const resultContainer = $('#webhook_result');
            
            if (!shopId) {
                resultContainer.html('<div class="notice notice-error inline"><p>Please select a shop.</p></div>');
                return;
            }
            
            // Show loading message
            resultContainer.html('<div class="notice notice-info inline"><p>' + wpwprintifysyncSettings.i18n.registering + '</p></div>');
            
            // Make AJAX request
            $.ajax({
                url: wpwprintifysyncSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wpwprintifysync_register_webhook',
                    nonce: wpwprintifysyncSettings.nonce,
                    shop_id: shopId
                },
                success: function(response) {
                    if (response.success) {
                        // Success
                        resultContainer.html('<div class="notice notice-success inline"><p>' + wpwprintifysyncSettings.i18n.registerSuccess + '</p></div>');
                    } else {
                        // Error
                        resultContainer.html('<div class="notice notice-error inline"><p>' + wpwprintifysyncSettings.i18n.registerFailed + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    // AJAX error
                    resultContainer.html('<div class="notice notice-error inline"><p>Server error. Please try again.</p></div>');
                }
            });
        });
    });
})(jQuery);