/**
 * Settings page JavaScript
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

jQuery(document).ready(function($) {
    
    // Form submission for API settings
    $('#api-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const apiKey = $('#api-key').val();
        const apiEndpoint = $('#api-endpoint').val();
        
        // Validate inputs
        if (!apiKey) {
            showMessage('#settings-message', 'API key is required.', 'danger');
            return;
        }
        
        // Save API settings
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_save_api_settings',
                nonce: wpwps.nonce,
                api_key: apiKey,
                api_endpoint: apiEndpoint
            },
            beforeSend: function() {
                showMessage('#settings-message', 'Saving settings...', 'info');
            },
            success: function(response) {
                if (response.success) {
                    showMessage('#settings-message', response.data.message, 'success');
                    $('#shop-selection-card').show();
                } else {
                    showMessage('#settings-message', response.data.message, 'danger');
                }
            },
            error: function() {
                showMessage('#settings-message', 'An error occurred while saving settings.', 'danger');
            }
        });
    });
    
    // Test API connection
    $('#test-connection').on('click', function() {
        const apiKey = $('#api-key').val();
        
        if (!apiKey) {
            showMessage('#settings-message', 'API key is required to test connection.', 'danger');
            return;
        }
        
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_connection',
                nonce: wpwps.nonce
            },
            beforeSend: function() {
                showMessage('#settings-message', 'Testing connection...', 'info');
            },
            success: function(response) {
                if (response.success) {
                    showMessage('#settings-message', response.data.message, 'success');
                    
                    // Show shop selection card
                    $('#shop-selection-card').show();
                    
                    // If we have shops data, populate the dropdown
                    if (response.data.shops) {
                        populateShopDropdown(response.data.shops);
                    }
                } else {
                    showMessage('#settings-message', response.data.message, 'danger');
                }
            },
            error: function() {
                showMessage('#settings-message', 'An error occurred while testing connection.', 'danger');
            }
        });
    });
    
    // Fetch shops from API
    $('#fetch-shops').on('click', function() {
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_connection',
                nonce: wpwps.nonce
            },
            beforeSend: function() {
                showMessage('#shop-message', 'Fetching shops...', 'info');
                $('#shop-select').prop('disabled', true);
                $('#save-shop').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    showMessage('#shop-message', 'Shops fetched successfully!', 'success');
                    
                    // Populate shop dropdown
                    if (response.data.shops) {
                        populateShopDropdown(response.data.shops);
                    }
                } else {
                    showMessage('#shop-message', response.data.message, 'danger');
                }
            },
            error: function() {
                showMessage('#shop-message', 'An error occurred while fetching shops.', 'danger');
            }
        });
    });
    
    // Save selected shop
    $('#shop-selection-form').on('submit', function(e) {
        e.preventDefault();
        
        const shopId = $('#shop-select').val();
        const shopName = $('#shop-select option:selected').text();
        
        if (!shopId) {
            showMessage('#shop-message', 'Please select a shop.', 'danger');
            return;
        }
        
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_save_shop_id',
                nonce: wpwps.nonce,
                shop_id: shopId,
                shop_name: shopName
            },
            beforeSend: function() {
                showMessage('#shop-message', 'Saving shop ID...', 'info');
            },
            success: function(response) {
                if (response.success) {
                    showMessage('#shop-message', response.data.message, 'success');
                    
                    // Reload the page after a short delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage('#shop-message', response.data.message, 'danger');
                }
            },
            error: function() {
                showMessage('#shop-message', 'An error occurred while saving shop ID.', 'danger');
            }
        });
    });
    
    /**
     * Show a message in the specified container
     * 
     * @param {string} container The container selector
     * @param {string} message The message to show
     * @param {string} type The message type (success, info, warning, danger)
     */
    function showMessage(container, message, type) {
        const $container = $(container);
        $container.removeClass('d-none alert-success alert-info alert-warning alert-danger');
        $container.addClass('alert-' + type);
        $container.html(message);
    }
    
    /**
     * Populate the shop dropdown
     * 
     * @param {Array} shops The shops array
     */
    function populateShopDropdown(shops) {
        const $select = $('#shop-select');
        $select.empty();
        
        $select.append('<option value="">' + 'Select a shop' + '</option>');
        
        shops.forEach(function(shop) {
            $select.append('<option value="' + shop.id + '">' + shop.title + '</option>');
        });
        
        $select.prop('disabled', false);
        $('#save-shop').prop('disabled', false);
    }
    
    // ChatGPT API Settings Handlers
    
    // Toggle API key visibility
    $('#toggle-api-key').on('click', function() {
        const apiKeyField = $('#chatgpt-api-key');
        const eyeIcon = $(this).find('i');
        
        if (apiKeyField.attr('type') === 'password') {
            apiKeyField.attr('type', 'text');
            eyeIcon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            apiKeyField.attr('type', 'password');
            eyeIcon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Update temperature display value
    $('#chatgpt-temperature').on('input', function() {
        $('#temperature-value').text($(this).val());
    });
    
    // Toggle usage limit inputs
    $('#chatgpt-enable-usage-limit').on('change', function() {
        if ($(this).is(':checked')) {
            $('#usage-limit-container').show();
        } else {
            $('#usage-limit-container').hide();
        }
    });
    
    // Save ChatGPT API settings
    $('#chatgpt-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const apiKey = $('#chatgpt-api-key').val();
        const model = $('#chatgpt-model').val();
        const maxTokens = $('#chatgpt-max-tokens').val();
        const temperature = $('#chatgpt-temperature').val();
        const enableUsageLimit = $('#chatgpt-enable-usage-limit').is(':checked');
        const monthlyLimit = $('#chatgpt-monthly-limit').val();
        
        if (!apiKey) {
            showChatGptMessage('API key is required.', 'danger');
            return;
        }
        
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_save_chatgpt_settings',
                nonce: wpwps.nonce,
                api_key: apiKey,
                model: model,
                max_tokens: maxTokens,
                temperature: temperature,
                enable_usage_limit: enableUsageLimit,
                monthly_limit: monthlyLimit
            },
            beforeSend: function() {
                showChatGptMessage('Saving settings...', 'info');
            },
            success: function(response) {
                if (response.success) {
                    showChatGptMessage(response.data.message, 'success');
                } else {
                    showChatGptMessage(response.data.message, 'danger');
                }
            },
            error: function() {
                showChatGptMessage('An error occurred while saving settings.', 'danger');
            }
        });
    });
    
    // Test ChatGPT API connection
    $('#test-chatgpt-api').on('click', function() {
        const apiKey = $('#chatgpt-api-key').val();
        
        if (!apiKey) {
            showChatGptMessage('API key is required to test connection.', 'danger');
            return;
        }
        
        const btn = $(this);
        const originalText = btn.html();
        
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_chatgpt',
                nonce: wpwps.nonce
            },
            beforeSend: function() {
                showChatGptMessage('Testing ChatGPT API connection...', 'info');
                btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Testing...');
                btn.prop('disabled', true);
                $('#chatgpt-response-container').addClass('d-none');
            },
            success: function(response) {
                btn.html(originalText);
                btn.prop('disabled', false);
                
                if (response.success) {
                    showChatGptMessage(response.data.message, 'success');
                    
                    // Show response and usage info
                    $('#chatgpt-response').text(response.data.response);
                    
                    if (response.data.usage) {
                        const usage = response.data.usage;
                        const tokenInfo = `Tokens: ${usage.total_tokens} (${usage.prompt_tokens} prompt, ${usage.completion_tokens} completion)`;
                        const costInfo = `Cost: $${usage.cost.toFixed(6)}`;
                        
                        if (usage.monthly_limit !== null) {
                            const usagePercent = (usage.current_usage / usage.monthly_limit) * 100;
                            const usageInfo = `Monthly usage: $${usage.current_usage.toFixed(4)} / $${usage.monthly_limit.toFixed(2)} (${usagePercent.toFixed(1)}%)`;
                            $('#token-usage').html(`${tokenInfo} | ${costInfo} | ${usageInfo}`);
                        } else {
                            $('#token-usage').html(`${tokenInfo} | ${costInfo}`);
                        }
                    }
                    
                    $('#chatgpt-response-container').removeClass('d-none');
                } else {
                    showChatGptMessage(response.data.message, 'danger');
                }
            },
            error: function() {
                btn.html(originalText);
                btn.prop('disabled', false);
                showChatGptMessage('An error occurred while testing connection.', 'danger');
            }
        });
    });
    
    /**
     * Show a message in the ChatGPT settings card
     * 
     * @param {string} message The message to show
     * @param {string} type The message type (success, info, warning, danger)
     */
    function showChatGptMessage(message, type) {
        const $container = $('#chatgpt-message');
        $container.removeClass('d-none alert-success alert-info alert-warning alert-danger');
        $container.addClass('alert-' + type);
        $container.html(message);
    }
    
    // Fetch shop name button handler
    $('#fetch-shop-name').on('click', function() {
        console.log('Fetch shop name button clicked');
        const btn = $(this);
        const originalHtml = btn.html();
        
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_fetch_shop_name',
                nonce: wpwps.nonce
            },
            beforeSend: function() {
                console.log('Sending AJAX request to fetch shop name');
                btn.prop('disabled', true)
                   .html('<i class="fas fa-spinner fa-spin me-1"></i> ' + 'Fetching...');
                
                showNameFetchMessage('Fetching shop name from Printify...', 'info');
            },
            success: function(response) {
                console.log('Shop name fetch response:', response);
                btn.prop('disabled', false)
                   .html(originalHtml);
                
                if (response.success) {
                    showNameFetchMessage(response.data.message, 'success');
                    
                    // Reload the page after a short delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNameFetchMessage(response.data.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Shop name fetch error:', error, xhr.responseText);
                btn.prop('disabled', false)
                   .html(originalHtml);
                
                showNameFetchMessage('An error occurred while fetching shop name.', 'danger');
            }
        });
    });

    // Second fetch shop name button (in shop info card)
    $('#shop-info-fetch').on('click', function() {
        $('#fetch-shop-name').trigger('click');
    });

    /**
     * Show a message in the name fetch alert
     * 
     * @param {string} message The message to show
     * @param {string} type The message type (success, info, warning, danger)
     */
    function showNameFetchMessage(message, type) {
        const $container = $('#name-fetch-alert');
        $container.removeClass('d-none alert-success alert-info alert-warning alert-danger');
        $container.addClass('alert-' + type);
        $container.html(message);
    }
});
