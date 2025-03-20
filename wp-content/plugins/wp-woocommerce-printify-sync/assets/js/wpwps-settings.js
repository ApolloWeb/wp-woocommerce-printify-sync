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
                shop_id: shopId
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
});
