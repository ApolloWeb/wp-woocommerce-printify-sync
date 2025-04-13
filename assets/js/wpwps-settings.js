/**
 * WP WooCommerce Printify Sync - Settings Page JavaScript
 */
(function($) {
    'use strict';

    // DOM ready
    $(function() {
        // ---------- Printify Settings ----------
        
        // Toggle API key visibility
        $('#toggle-api-key').on('click', function() {
            const input = $('#printify-api-key');
            const icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Test Printify connection
        $('#test-printify-connection').on('click', function() {
            const apiKey = $('#printify-api-key').val();
            const apiEndpoint = $('#printify-api-endpoint').val();
            
            if (!apiKey) {
                showAlert('#printify-test-result', 'danger', 'API key is required.');
                return;
            }
            
            // Show loading
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
            
            // Clear previous results
            $('#printify-test-result').hide();
            
            // Send AJAX request
            $.ajax({
                url: wpwps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_test_printify_connection',
                    nonce: wpwps_ajax.nonce,
                    api_key: apiKey,
                    api_endpoint: apiEndpoint
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showAlert('#printify-test-result', 'success', response.data.message);
                        
                        // Populate shops dropdown
                        if (response.data.shops && response.data.shops.length > 0) {
                            populateShopsDropdown(response.data.shops);
                        }
                    } else {
                        // Show error message
                        showAlert('#printify-test-result', 'danger', response.data.message);
                    }
                },
                error: function() {
                    showAlert('#printify-test-result', 'danger', 'An error occurred while testing the connection.');
                },
                complete: function() {
                    // Reset button
                    $('#test-printify-connection').prop('disabled', false).html('<i class="fas fa-plug"></i> Test Connection');
                }
            });
        });
        
        // Save Printify settings
        $('#save-printify-settings').on('click', function() {
            const apiKey = $('#printify-api-key').val();
            const apiEndpoint = $('#printify-api-endpoint').val();
            const shopId = $('#printify-shop-id').val();
            
            if (!apiKey) {
                showAlert('#printify-test-result', 'danger', 'API key is required.');
                return;
            }
            
            // Show loading
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            
            // Send AJAX request
            $.ajax({
                url: wpwps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_save_printify_settings',
                    nonce: wpwps_ajax.nonce,
                    api_key: apiKey,
                    api_endpoint: apiEndpoint,
                    shop_id: shopId
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showAlert('#printify-test-result', 'success', response.data.message);
                        
                        // If shop ID was saved, reload the page
                        if (shopId) {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        // Show error message
                        showAlert('#printify-test-result', 'danger', response.data.message);
                    }
                },
                error: function() {
                    showAlert('#printify-test-result', 'danger', 'An error occurred while saving settings.');
                },
                complete: function() {
                    // Reset button
                    $('#save-printify-settings').prop('disabled', false).html('<i class="fas fa-save"></i> Save Settings');
                }
            });
        });
        
        // ---------- ChatGPT Settings ----------
        
        // Toggle ChatGPT API key visibility
        $('#toggle-chatgpt-key').on('click', function() {
            const input = $('#chatgpt-api-key');
            const icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Test ChatGPT connection
        $('#test-chatgpt-connection').on('click', function() {
            const apiKey = $('#chatgpt-api-key').val();
            
            if (!apiKey) {
                showAlert('#chatgpt-test-result', 'danger', 'API key is required.');
                return;
            }
            
            // Show loading
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
            
            // Clear previous results
            $('#chatgpt-test-result').hide();
            
            // Send AJAX request
            $.ajax({
                url: wpwps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_test_chatgpt_connection',
                    nonce: wpwps_ajax.nonce,
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showAlert('#chatgpt-test-result', 'success', response.data.message);
                    } else {
                        // Show error message
                        showAlert('#chatgpt-test-result', 'danger', response.data.message);
                    }
                },
                error: function() {
                    showAlert('#chatgpt-test-result', 'danger', 'An error occurred while testing the connection.');
                },
                complete: function() {
                    // Reset button
                    $('#test-chatgpt-connection').prop('disabled', false).html('<i class="fas fa-plug"></i> Test Connection');
                }
            });
        });
        
        // Save ChatGPT settings
        $('#save-chatgpt-settings').on('click', function() {
            const apiKey = $('#chatgpt-api-key').val();
            const monthlyCap = $('#chatgpt-monthly-cap').val();
            const tokenLimit = $('#chatgpt-token-limit').val();
            const temperature = $('#chatgpt-temperature').val();
            
            if (!apiKey) {
                showAlert('#chatgpt-test-result', 'danger', 'API key is required.');
                return;
            }
            
            // Show loading
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            
            // Send AJAX request
            $.ajax({
                url: wpwps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_save_chatgpt_settings',
                    nonce: wpwps_ajax.nonce,
                    api_key: apiKey,
                    monthly_cap: monthlyCap,
                    token_limit: tokenLimit,
                    temperature: temperature
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showAlert('#chatgpt-test-result', 'success', response.data.message);
                    } else {
                        // Show error message
                        showAlert('#chatgpt-test-result', 'danger', response.data.message);
                    }
                },
                error: function() {
                    showAlert('#chatgpt-test-result', 'danger', 'An error occurred while saving settings.');
                },
                complete: function() {
                    // Reset button
                    $('#save-chatgpt-settings').prop('disabled', false).html('<i class="fas fa-save"></i> Save Settings');
                }
            });
        });
        
        // Calculate cost estimate
        $('#calculate-cost').on('click', function() {
            const tokenLimit = $('#chatgpt-token-limit').val();
            const estimatedTickets = $('#estimated-tickets').val();
            
            if (!tokenLimit || !estimatedTickets) {
                showAlert('#cost-estimate-result', 'danger', 'Token limit and estimated tickets are required.');
                return;
            }
            
            // Show loading
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Calculating...');
            
            // Send AJAX request
            $.ajax({
                url: wpwps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_estimate_chatgpt_cost',
                    nonce: wpwps_ajax.nonce,
                    token_limit: tokenLimit,
                    estimated_tickets: estimatedTickets
                },
                success: function(response) {
                    if (response.success) {
                        // Show cost estimate
                        $('#estimated-cost').text('$' + response.data.estimated_cost);
                        $('#cost-estimate-result').fadeIn();
                    } else {
                        showAlert('#cost-estimate-result', 'danger', response.data.message || 'An error occurred while calculating the cost.');
                    }
                },
                error: function() {
                    showAlert('#cost-estimate-result', 'danger', 'An error occurred while calculating the cost.');
                },
                complete: function() {
                    // Reset button
                    $('#calculate-cost').prop('disabled', false).html('<i class="fas fa-calculator"></i> Calculate');
                }
            });
        });
        
        // ---------- Helper Functions ----------
        
        /**
         * Show alert message
         * 
         * @param {string} selector The selector for the alert container
         * @param {string} type     The type of alert (success, danger, warning, info)
         * @param {string} message  The message to display
         */
        function showAlert(selector, type, message) {
            const alertBox = $(selector);
            alertBox.removeClass('alert-success alert-danger alert-warning alert-info')
                .addClass('alert-' + type)
                .html(message)
                .fadeIn();
        }
        
        /**
         * Populate shops dropdown
         * 
         * @param {Array} shops Array of shop objects
         */
        function populateShopsDropdown(shops) {
            const dropdown = $('#printify-shop-id');
            dropdown.empty().append('<option value="">' + wpwps_ajax.i18n_select_shop + '</option>');
            
            $.each(shops, function(i, shop) {
                dropdown.append('<option value="' + shop.id + '">' + shop.title + '</option>');
            });
            
            $('#printify-shops-container').fadeIn();
        }
    });
})(jQuery);
