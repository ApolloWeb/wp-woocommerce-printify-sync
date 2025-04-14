jQuery(function($) {
    // Tab navigation
    $('#wpwps-settings-tabs a').on('click', function(e) {
        e.preventDefault();
        
        // Update active tab
        $('#wpwps-settings-tabs a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show the corresponding section
        var targetSection = $(this).data('tab');
        $('.wpwps-settings-section').removeClass('active');
        $('#' + targetSection).addClass('active');
    });
    
    // Printify connection test
    $('#wpwps-test-connection').on('click', function() {
        var apiKey = $('#printify_api_key').val();
        var endpoint = $('#printify_api_endpoint').val();
        $('#wpwps-test-result').text('Testing...');
        $.post(wpwpsSettings.ajaxUrl, {
            action: 'wpwps_test_printify_connection',
            nonce: wpwpsSettings.nonce,
            api_key: apiKey,
            endpoint: endpoint
        }, function(response) {
            if (response.success && response.data.shops) {
                $('#wpwps-test-result').html('<span class="text-success">Success!</span>');
                // Populate shop dropdown
                var $select = $('#printify_shop_id').empty();
                $.each(response.data.shops, function(i, shop) {
                    $select.append($('<option>', { value: shop.id, text: shop.title }));
                });
                $('#wpwps-shop-select-container').show();
            } else {
                $('#wpwps-test-result').html('<span class="text-danger">' + (response.data && response.data.message ? response.data.message : 'Error') + '</span>');
            }
        });
    });

    // ChatGPT test
    $('#wpwps-test-chatgpt').on('click', function() {
        var apiKey = $('#chatgpt_api_key').val();
        var maxTokens = $('#chatgpt_max_tokens').val();
        var monthlyCap = $('#chatgpt_monthly_cap').val();
        
        if (!apiKey) {
            $('#wpwps-chatgpt-result').html('<span class="text-danger">API Key is required</span>');
            return;
        }
        
        $('#wpwps-chatgpt-result').html('<span>Calculating...</span>');
        
        $.post(wpwpsSettings.ajaxUrl, {
            action: 'wpwps_test_chatgpt',
            nonce: wpwpsSettings.nonce,
            chatgpt_api_key: apiKey,
            chatgpt_max_tokens: maxTokens,
            chatgpt_monthly_cap: monthlyCap
        }, function(response) {
            if (response.success) {
                var html = '<div class="alert alert-info">';
                html += '<p>Estimated monthly usage: ' + response.data.estimated_monthly_tokens.toLocaleString() + ' tokens</p>';
                html += '<p>Estimated monthly cost: $' + response.data.estimated_monthly_cost.toFixed(2) + '</p>';
                
                if (response.data.within_cap) {
                    html += '<p class="mb-0 text-success">✓ Within your monthly cap</p>';
                } else {
                    html += '<p class="mb-0 text-danger">⚠ Exceeds your monthly cap</p>';
                }
                
                html += '</div>';
                $('#wpwps-chatgpt-result').html(html);
            } else {
                $('#wpwps-chatgpt-result').html('<span class="text-danger">' + (response.data && response.data.message ? response.data.message : 'Error') + '</span>');
            }
        });
    });
    
    // Temperature slider functionality
    $('#chatgpt_temperature').on('input', function() {
        $('#temperature-value').text($(this).val());
    });

    // Submit form - Fixed form handling
    $('#wpwps-settings-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var originalText = $submitButton.text();

        // Show loading state
        $submitButton.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

        // Get form data
        var formData = {};
        $form.find('input, select').each(function() {
            formData[this.name] = $(this).val();
        });
        
        // Add action and nonce
        formData.action = 'wpwps_save_settings';
        formData.nonce = wpwpsSettings.nonce;

        console.log('Sending data:', formData);

        // Make AJAX request
        $.ajax({
            url: wpwpsSettings.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                console.log('Response:', response);
                $submitButton.html(originalText).prop('disabled', false);

                if (response.success) {
                    $('<div class="alert alert-success mt-3">' + response.data.message + '</div>')
                        .insertAfter($submitButton)
                        .delay(3000)
                        .fadeOut(500);
                } else {
                    $('<div class="alert alert-danger mt-3">' + (response.data ? response.data.message : 'Error saving settings.') + '</div>')
                        .insertAfter($submitButton)
                        .delay(3000)
                        .fadeOut(500);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                
                $submitButton.html(originalText).prop('disabled', false);
                $('<div class="alert alert-danger mt-3">Error saving settings. Please try again.</div>')
                    .insertAfter($submitButton)
                    .delay(3000)
                    .fadeOut(500);
            }
        });
    });

    // Set initial values for sliders when settings are loaded
    function initializeSettingsForm() {
        if (typeof wpwpsSettings.savedValues !== 'undefined') {
            // Populate all saved values
            const settings = wpwpsSettings.savedValues;
            for (let key in settings) {
                const $input = $(`#${key}`);
                if ($input.length) {
                    $input.val(settings[key]);
                    // Update temperature display if needed
                    if (key === 'chatgpt_temperature') {
                        $('#temperature-value').text(settings[key]);
                    }
                }
            }
        }
    }

    // Initialize the form
    initializeSettingsForm();
});
