jQuery(document).ready(function($) {
    // Temperature slider update
    $('#temperature').on('input', function() {
        $('#temperatureValue').text($(this).val());
    });

    // Toast notification function
    function showToast(message, type = 'info', duration = 3000) {
        // Create container if it doesn't exist
        if (!$('#wpwps-toast-container').length) {
            $('body').append('<div id="wpwps-toast-container"></div>');
        }
        
        const toast = $('<div class="wpwps-toast">')
            .addClass('wpwps-toast-' + type)
            .appendTo('#wpwps-toast-container');
        
        // Add icon based on type
        let icon = 'info-circle';
        if (type === 'success') icon = 'check-circle';
        if (type === 'error') icon = 'times-circle';
        if (type === 'warning') icon = 'exclamation-circle';
        
        // Create toast content with icon and message
        $('<i class="fas fa-' + icon + '">').appendTo(toast);
        $('<span class="toast-message">').text(' ' + message).appendTo(toast);
        
        // Add close button
        $('<button type="button" class="wpwps-toast-close">')
            .html('&times;')
            .appendTo(toast)
            .on('click', function() {
                removeToast(toast);
            });
        
        // Show toast with animation
        requestAnimationFrame(() => {
            toast.addClass('wpwps-toast-show');
        });
        
        // Auto-remove after duration
        setTimeout(() => {
            removeToast(toast);
        }, duration);
    }
    
    function removeToast(toast) {
        toast.removeClass('wpwps-toast-show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }

    // Test Printify connection
    $('#testPrintifyConnection').on('click', function() {
        const button = $(this);
        const apiKey = $('#printify_api_key').val().trim();
        const endpoint = $('#printify_api_endpoint').val().trim() || 'https://api.printify.com/v1';

        if (!apiKey) {
            showToast('Please enter an API key', 'warning');
            return;
        }

        button.prop('disabled', true).text('Testing...');
        console.log('Testing Printify connection with endpoint:', endpoint);

        $.ajax({
            url: wpPrintifySync.ajaxUrl,
            type: 'POST',
            data: {
                action: 'test_printify_connection',
                nonce: wpPrintifySync.nonce,
                api_key: apiKey,
                endpoint: endpoint
            },
            success: function(response) {
                if (response.success && Array.isArray(response.data.shops)) {
                    $('#shopSelector').show();
                    const select = $('#printify_shop');
                    select.empty();
                    
                    response.data.shops.forEach(shop => {
                        select.append($('<option>', {
                            value: shop.id,
                            text: shop.title || shop.name
                        }));
                    });
                    
                    showToast('Connection successful! Shop list updated.', 'success');
                } else {
                    const msg = response.data?.message || 'Invalid response from Printify API';
                    showToast('Connection failed: ' + msg, 'error');
                    console.error('Printify API Error:', response);
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Connection test failed';
                try {
                    if (xhr.status === 500) {
                        errorMsg = 'Server error: The connection test failed due to an internal server error. Please check your server logs.';
                        console.error('Printify API Server Error:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText
                        });
                        showToast(errorMsg, 'error', 5000); // Show error for longer duration
                        return;
                    }

                    const response = JSON.parse(xhr.responseText);
                    if (response.data?.code === 429) {
                        errorMsg = 'Rate limit exceeded. Please wait a moment and try again.';
                        // Add retry button after rate limit window
                        setTimeout(() => {
                            button.prop('disabled', false).text('Retry Connection');
                        }, 60000); // 1 minute retry window
                        showToast(errorMsg, 'warning', 5000);
                        return;
                    }

                    errorMsg += ': ' + (response.data?.message || xhr.statusText || 'Unknown error');
                    console.error('Printify API Error:', response);
                } catch (e) {
                    if (xhr.status === 0) {
                        errorMsg = 'Network error: Unable to reach the server. Please check your internet connection.';
                    } else {
                        errorMsg += `. Server returned ${xhr.status} ${xhr.statusText}`;
                    }
                    console.error('Parse error:', e);
                }
                showToast(errorMsg, 'error');
            },
            complete: function() {
                // Only reset button if not rate limited
                if (!button.text().includes('Retry')) {
                    button.prop('disabled', false).text('Test Connection');
                }
            }
        });
    });

    // Test OpenAI connection
    $('#testOpenAI').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('Testing...');

        $.ajax({
            url: wpPrintifySync.ajaxUrl,
            type: 'POST',
            data: {
                action: 'test_openai_connection',
                nonce: wpPrintifySync.nonce,
                api_key: $('#openai_api_key').val(),
                token_limit: $('#token_limit').val(),
                temperature: $('#temperature').val(),
                monthly_spend_cap: $('#monthly_spend_cap').val()
            },
            success: function(response) {
                if (response.success) {
                    showToast(`Test successful! Estimated monthly cost: $${response.data.estimated_cost}`, 'success');
                    $('#creditBalance')
                        .text('$' + response.data.credit_balance)
                        .removeClass('alert-danger alert-info')
                        .addClass(response.data.credit_balance < 2 ? 'alert-danger' : 'alert-info');
                } else {
                    showToast('Test failed: ' + response.data.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'OpenAI test failed';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg += ': ' + (response.data?.message || 'Please check your API key');
                } catch (e) {
                    errorMsg += '. Please try again.';
                }
                showToast(errorMsg, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Test & Estimate Cost');
            }
        });
    });

    // Save all settings
    $('#saveSettings').on('click', function() {
        if (!$('#printify_api_key').val()) {
            showToast('Please enter a Printify API key', 'warning');
            return;
        }

        const button = $(this);
        const endpoint = $('#printify_api_endpoint').val() || 'https://api.printify.com/v1';
        button.prop('disabled', true).text('Saving...');

        $.ajax({
            url: wpPrintifySync.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_settings',
                nonce: wpPrintifySync.nonce,
                printify_api_key: $('#printify_api_key').val(),
                printify_api_endpoint: endpoint,
                printify_shop: $('#printify_shop').val(),
                openai_api_key: $('#openai_api_key').val(),
                token_limit: $('#token_limit').val(),
                temperature: $('#temperature').val(),
                monthly_spend_cap: $('#monthly_spend_cap').val()
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.data.message || 'Settings saved successfully!', 'success');
                    
                    // Update credit balance if provided
                    if (response.data.credit_balance !== undefined) {
                        $('#creditBalance')
                            .text('$' + response.data.credit_balance)
                            .removeClass('alert-danger alert-info')
                            .addClass(response.data.credit_balance < 2 ? 'alert-danger' : 'alert-info');
                    }

                    if ($('#printify_shop').val()) {
                        $('#printify_shop').prop('disabled', true);
                        setTimeout(() => {
                            showToast('Shop selection locked. Contact support to change shops.', 'info', 5000);
                        }, 1000);
                    }
                } else {
                    showToast(response.data.message || 'Failed to save settings', 'error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to save settings';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg += ': ' + (response.data?.message || 'Please check your input');
                } catch (e) {
                    errorMsg += '. Please try again.';
                }
                showToast(errorMsg, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Save All Settings');
            }
        });
    });

    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        const input = $(this).closest('.input-group').find('input');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Initialize charts if they exist
    if (document.getElementById('syncChart')) {
        const syncCtx = document.getElementById('syncChart').getContext('2d');
        new Chart(syncCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Synced Products',
                    data: [65, 78, 90, 105, 125, 138],
                    borderColor: '#4f5b93',
                    backgroundColor: 'rgba(79, 91, 147, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    }
    
    if (document.getElementById('usageChart')) {
        const usageCtx = document.getElementById('usageChart').getContext('2d');
        new Chart(usageCtx, {
            type: 'doughnut',
            data: {
                labels: ['API Calls', 'Remaining'],
                datasets: [{
                    data: [75, 25],
                    backgroundColor: ['#96588a', '#e9ecef'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%'
            }
        });
    }
});
