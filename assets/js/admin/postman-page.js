<<<<<<< HEAD
/**
 * Postman Page JavaScript
 * 
 * Handles the Postman API testing interface
 */

jQuery(document).ready(function($) {
    // Add header row button
    $('.add-header').on('click', function() {
        var headerRow = $('<div class="header-row"></div>');
        headerRow.append('<input type="text" name="header_keys[]" placeholder="Header Name" />');
        headerRow.append('<input type="text" name="header_values[]" placeholder="Header Value" />');
        
        $('.headers-container').append(headerRow);
    });
    
    // Clear form button
    $('#clear-form').on('click', function() {
        $('#request-body').val('');
        $('#response-display').empty();
        $('#response-headers-display').empty();
        
        // Reset headers except the first two rows
        $('.header-row:gt(1)').remove();
        $('.header-row:lt(2) input').each(function(index) {
            if (index !== 0 && index !== 2) { // Keep first header name and second header name
                $(this).val('');
            }
        });
    });
    
    // Handle form submission
    $('#postman-form').on('submit', function(e) {
        e.preventDefault();
        
        var method = $('#request-method').val();
        var url = $('#request-url').val();
        var body = $('#request-body').val();
        
        // Collect headers
        var headers = {};
        $('.header-row').each(function() {
            var inputs = $(this).find('input');
            var key = $(inputs[0]).val().trim();
            var value = $(inputs[1]).val().trim();
            
            if (key) {
                headers[key] = value;
            }
        });
        
        // Show loading state
        $('#response-display').html('Loading...');
        $('#response-headers-display').html('Loading...');
        
        // Make AJAX request to server-side handler
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'printify_api_request',
                method: method,
                request_url: url,
                headers: headers,
                body: body,
                nonce: printify_postman.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Format JSON response if it's valid JSON
                    var responseBody = response.data.body;
                    try {
                        var jsonObj = JSON.parse(responseBody);
                        responseBody = JSON.stringify(jsonObj, null, 2);
                    } catch (e) {
                        // Not JSON, leave as is
                    }
                    
                    // Display response
                    $('#response-display').html(responseBody);
                    
                    // Format and display headers
                    var headersText = '';
                    if (response.data.headers) {
                        for (var key in response.data.headers) {
                            headersText += key + ': ' + response.data.headers[key] + '\n';
                        }
                    }
                    $('#response-headers-display').html(headersText);
                } else {
                    $('#response-display').html('Error: ' + response.data.message);
                    $('#response-headers-display').html('');
                }
            },
            error: function() {
                $('#response-display').html('AJAX request failed. Please check your connection.');
                $('#response-headers-display').html('');
            }
        });
    });
    
    // Initialize with some predefined values based on selected API
    $('#api-select').on('change', function() {
        var apiType = $(this).val();
        
        if (apiType === 'printify') {
            $('#request-url').val('https://api.printify.com/v1/shops.json');
            $($('.header-row').get(0)).find('input').eq(1).val('Bearer YOUR_PRINTIFY_API_KEY');
        } else if (apiType === 'woocommerce') {
            $('#request-url').val(printify_postman.site_url + '/wp-json/wc/v3/products');
            $($('.header-row').get(0)).find('input').eq(0).val('Authorization');
            $($('.header-row').get(0)).find('input').eq(1).val('Basic ' + btoa('YOUR_CONSUMER_KEY:YOUR_CONSUMER_SECRET'));
        }
    });
});
=======
jQuery(document).ready(function($)

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: jQuery(document).ready(function($)
#
#
# Commit Hash 16c804f
#
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
