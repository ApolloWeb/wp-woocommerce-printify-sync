jQuery(document).ready(function($) {
    // Initialize toast notifications
    WPWPSToast.init();

    // Toggle password visibility
    $('#toggle-api-key, #toggle-chatgpt-key').on('click', function() {
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

    // Temperature range value display
    $('#temperature').on('input', function() {
        $('#temperature-value').text($(this).val());
    });

    // Test Printify connection
    $('#test-printify-connection').on('click', function() {
        const button = $(this);
        const originalText = button.html();
        button.html('<i class="fas fa-spinner fa-spin me-2"></i>Testing...');
        button.prop('disabled', true);

        // Hide previous results
        $('#connection-results, #shop-selector').hide();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_test_printify',
                api_key: $('#printify_api_key').val(),
                api_endpoint: $('#printify_api_endpoint').val(),
                nonce: wpwps_settings.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show connection results
                    $('#profile-name').text(response.data.profile.name || 'N/A');
                    $('#profile-email').text(response.data.profile.email || 'N/A');
                    $('#shops-count').text(response.data.shops.length);
                    $('#connection-results').slideDown();
                    
                    // Populate shop selector
                    const select = $('#printify_shop_id');
                    select.empty().append('<option value="">Select a shop...</option>');
                    response.data.shops.forEach(function(shop) {
                        select.append(`<option value="${shop.id}">${shop.title}</option>`);
                    });
                    
                    $('#shop-selector').slideDown();
                    WPWPSToast.success('Success', 'Connection successful!');
                } else {
                    handleApiError(response.data);
                }
            },
            error: function(xhr) {
                let error = {
                    message: 'Connection failed',
                    code: xhr.status
                };
                try {
                    error = JSON.parse(xhr.responseText).data;
                } catch (e) {}
                handleApiError(error);
            },
            complete: function() {
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });

    // Save Printify settings
    $('#wpwps-printify-settings').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_save_printify_settings',
                settings: form.serialize(),
                nonce: wpwps_settings.nonce
            },
            success: function(response) {
                if (response.success) {
                    WPWPSToast.success('Success', 'Settings saved successfully!');
                } else {
                    handleApiError(response.data);
                }
            },
            error: function(xhr) {
                handleApiError({
                    message: 'Failed to save settings',
                    code: xhr.status
                });
            },
            complete: function() {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    });

    // Handle API errors
    function handleApiError(error) {
        let message = error.message || 'An unknown error occurred';
        let code = error.code || 500;
        
        // Format message for display
        if (code) {
            message = `Error ${code}: ${message}`;
        }
        
        WPWPSToast.error('Error', message);
    }

    // ChatGPT Settings handling
    $('#test-chatgpt').on('click', function() {
        const button = $(this);
        const originalText = button.html();
        button.html('<i class="fas fa-spinner fa-spin me-2"></i>Calculating...');
        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_test_chatgpt',
                monthly_cap: $('#monthly_cap').val(),
                tokens: $('#tokens').val(),
                nonce: wpwps_settings.nonce
            },
            success: function(response) {
                if (response.success) {
                    WPWPSToast.success('Cost Estimate', response.data.message);
                } else {
                    handleApiError(response.data);
                }
            },
            error: function(xhr) {
                handleApiError({
                    message: 'Failed to calculate estimate',
                    code: xhr.status
                });
            },
            complete: function() {
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });

    // Save ChatGPT settings
    $('#wpwps-chatgpt-settings').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_save_chatgpt_settings',
                settings: form.serialize(),
                nonce: wpwps_settings.nonce
            },
            success: function(response) {
                if (response.success) {
                    WPWPSToast.success('Success', 'Settings saved successfully!');
                } else {
                    handleApiError(response.data);
                }
            },
            error: function(xhr) {
                handleApiError({
                    message: 'Failed to save settings',
                    code: xhr.status
                });
            },
            complete: function() {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    });
});