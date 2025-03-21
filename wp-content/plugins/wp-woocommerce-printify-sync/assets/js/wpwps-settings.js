/**
 * Settings page JavaScript.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

jQuery(document).ready(function($) {
    
    // Handle toggling API key visibility
    $('#toggle-printify-key').on('click', function() {
        const input = $('#printify_api_key');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    $('#toggle-chatgpt-key').on('click', function() {
        const input = $('#chatgpt_api_key');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Update temperature value display when slider changes
    $('#chatgpt_temperature').on('input', function() {
        $('#temperature_value').text($(this).val());
    });
    
    // Test Printify API connection
    $('#test-printify-connection').on('click', function() {
        const button = $(this);
        const resultContainer = $('#printify-connection-result');
        const apiKey = $('#printify_api_key').val();
        const apiEndpoint = $('#printify_api_endpoint').val();
        
        if (!apiKey) {
            resultContainer.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Please enter your Printify API key.</div>');
            return;
        }
        
        // Show loading state
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        resultContainer.html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Testing connection to Printify API...</div>');
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_printify_connection',
                nonce: wpwps_data.nonce,
                api_key: apiKey,
                api_endpoint: apiEndpoint
            },
            success: function(response) {
                if (response.success) {
                    // Connection successful
                    resultContainer.html('<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + response.data.message + '</div>');
                    
                    // Populate shop dropdown
                    const shopSelect = $('#printify_shop_id');
                    shopSelect.empty();
                    shopSelect.append('<option value="">' + wpwps_settings.select_shop + '</option>');
                    
                    if (response.data.shops && response.data.shops.length > 0) {
                        $.each(response.data.shops, function(i, shop) {
                            shopSelect.append('<option value="' + shop.id + '">' + shop.title + '</option>');
                        });
                    }
                } else {
                    // Connection failed
                    resultContainer.html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + response.data.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                resultContainer.html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> An error occurred while testing the connection.</div>');
            },
            complete: function() {
                // Reset button state
                button.prop('disabled', false);
                button.html('<i class="fas fa-plug"></i> Test Connection');
            }
        });
    });
    
    // Test ChatGPT API connection
    $('#test-chatgpt-connection').on('click', function() {
        const button = $(this);
        const resultContainer = $('#chatgpt-connection-result');
        const apiKey = $('#chatgpt_api_key').val();
        const temperature = $('#chatgpt_temperature').val();
        const monthlyBudget = $('#chatgpt_monthly_budget').val();
        
        if (!apiKey) {
            resultContainer.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Please enter your ChatGPT API key.</div>');
            return;
        }
        
        // Show loading state
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        resultContainer.html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Testing connection to ChatGPT API...</div>');
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_chatgpt_connection',
                nonce: wpwps_data.nonce,
                api_key: apiKey,
                temperature: temperature,
                monthly_budget: monthlyBudget
            },
            success: function(response) {
                if (response.success) {
                    // Connection successful
                    let html = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + response.data.message + '</div>';
                    
                    // Add cost estimate
                    if (response.data.estimated_cost) {
                        const estimate = response.data.estimated_cost;
                        html += '<div class="card mt-3"><div class="card-header">Monthly Cost Estimate</div><div class="card-body">';
                        html += '<p>Based on your test, here\'s an estimated monthly usage:</p>';
                        html += '<ul>';
                        html += '<li>Tokens per request: <strong>' + estimate.tokens_per_request + '</strong></li>';
                        html += '<li>Estimated email tickets per day: <strong>' + estimate.emails_per_day + '</strong></li>';
                        html += '<li>Estimated email tickets per month: <strong>' + estimate.emails_per_month + '</strong></li>';
                        html += '<li>Estimated monthly tokens: <strong>' + estimate.estimated_monthly_tokens + '</strong></li>';
                        html += '<li>Estimated monthly cost: <strong>$' + estimate.estimated_monthly_cost.toFixed(2) + '</strong></li>';
                        html += '</ul>';
                        html += '</div></div>';
                    }
                    
                    resultContainer.html(html);
                } else {
                    // Connection failed
                    resultContainer.html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + response.data.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                resultContainer.html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> An error occurred while testing the connection.</div>');
            },
            complete: function() {
                // Reset button state
                button.prop('disabled', false);
                button.html('<i class="fas fa-plug"></i> Test Connection & Estimate Costs');
            }
        });
    });
    
    // Handle shop selection
    $('#printify_shop_id').on('change', function() {
        const shopId = $(this).val();
        const shopName = $(this).find('option:selected').text();
        
        if (shopId) {
            $('#printify_shop_name').val(shopName);
        } else {
            $('#printify_shop_name').val('');
        }
    });
    
    // Save settings
    $('#wpwps-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const saveButton = $('#save-settings');
        const resultSpan = $('#save-settings-result');
        
        // Validate required fields
        if (!$('#printify_api_key').val()) {
            resultSpan.html('<span class="text-danger">Printify API key is required.</span>');
            return;
        }
        
        // Show loading state
        saveButton.prop('disabled', true);
        saveButton.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        resultSpan.html('');
        
        // Gather form data
        const formData = {
            action: 'wpwps_save_settings',
            nonce: wpwps_data.nonce,
            printify_api_key: $('#printify_api_key').val(),
            printify_api_endpoint: $('#printify_api_endpoint').val(),
            printify_shop_id: $('#printify_shop_id').val(),
            printify_shop_name: $('#printify_shop_name').val(),
            chatgpt_api_key: $('#chatgpt_api_key').val(),
            chatgpt_temperature: $('#chatgpt_temperature').val(),
            chatgpt_monthly_budget: $('#chatgpt_monthly_budget').val(),
            log_level: $('#log_level').val()
        };
        
        // Make AJAX request
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    resultSpan.html('<span class="text-success"><i class="fas fa-check-circle"></i> ' + response.data.message + '</span>');
                    
                    // If shop ID was set, disable the select
                    if (formData.printify_shop_id) {
                        $('#printify_shop_id').prop('disabled', true);
                    }
                } else {
                    resultSpan.html('<span class="text-danger"><i class="fas fa-times-circle"></i> ' + response.data.message + '</span>');
                }
            },
            error: function(xhr, status, error) {
                resultSpan.html('<span class="text-danger"><i class="fas fa-times-circle"></i> An error occurred while saving settings.</span>');
            },
            complete: function() {
                // Reset button state
                saveButton.prop('disabled', false);
                saveButton.html('<i class="fas fa-save"></i> Save Settings');
            }
        });
    });
});

// Localized strings for translations
const wpwps_settings = {
    select_shop: 'Select a shop'
};
