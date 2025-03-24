/**
 * Settings page specific JavaScript
 */

(function($) {
    'use strict';
    
    // Handle API connection testing
    function setupAPITesting() {
        $('#wpwps-test-api-connection').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.html();
            
            // Show loading state
            $btn.prop('disabled', true);
            $btn.html('<span class="wpwps-spinner"></span> Testing...');
            
            // Get the API key
            const apiKey = $('#api_key').val();
            
            // Send AJAX request
            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_test_printify_api',
                    nonce: wpwps.nonce,
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        wpwpsToast.success(response.data.message, 'API Connection');
                        
                        // Show shop selection container and populate dropdown
                        $('#shop-selection-container').removeClass('d-none');
                        
                        const $shopSelect = $('#shop_id');
                        $shopSelect.empty();
                        $shopSelect.append('<option value="" selected disabled>' + wpwps.i18n.select_shop + '</option>');
                        
                        if (response.data.shops && response.data.shops.length > 0) {
                            $.each(response.data.shops, function(i, shop) {
                                $shopSelect.append('<option value="' + shop.id + '">' + shop.name + '</option>');
                            });
                        }
                    } else {
                        wpwpsToast.error(response.data.message, 'API Error');
                    }
                },
                error: function() {
                    wpwpsToast.error(wpwps.i18n.ajax_error, 'Error');
                },
                complete: function() {
                    // Restore button state
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                }
            });
        });
    }
    
    // Handle OpenAI API testing
    function setupOpenAITesting() {
        $('#wpwps-test-openai-connection').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.html();
            
            // Show loading state
            $btn.prop('disabled', true);
            $btn.html('<span class="wpwps-spinner"></span> Testing...');
            
            // Get OpenAI settings
            const apiKey = $('#openai_api_key').val();
            const model = $('#openai_model').val();
            const temperature = $('#openai_temperature').val();
            
            // Send AJAX request
            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_test_openai_api',
                    nonce: wpwps.nonce,
                    api_key: apiKey,
                    model: model,
                    temperature: temperature
                },
                success: function(response) {
                    if (response.success) {
                        wpwpsToast.success(response.data.message, 'OpenAI Connection');
                        
                        // Update usage stats if available
                        if (response.data.data) {
                            const data = response.data.data;
                            const usagePercent = data.monthly_cap > 0 ? 
                                Math.min(100, Math.round(data.current_month_usage / data.monthly_cap * 100)) : 0;
                                
                            $('.wpwps-progress-bar').css('width', usagePercent + '%').text(usagePercent + '%');
                            
                            $('.wpwps-usage-stats').html(
                                '<div>' + 
                                numberWithCommas(data.current_month_usage) + 
                                ' / ' + 
                                numberWithCommas(data.monthly_cap) + 
                                ' ' + wpwps.i18n.tokens + '</div>' +
                                '<div>' + wpwps.i18n.estimated_cost + ' $' + 
                                parseFloat(data.estimated_cost).toFixed(2) + '</div>'
                            );
                        }
                    } else {
                        wpwpsToast.error(response.data.message, 'API Error');
                    }
                },
                error: function() {
                    wpwpsToast.error(wpwps.i18n.ajax_error, 'Error');
                },
                complete: function() {
                    // Restore button state
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                }
            });
        });
    }
    
    // Handle shop selection and saving
    function setupShopSelection() {
        $('#shop_id').on('change', function() {
            const shopId = $(this).val();
            const shopName = $(this).find('option:selected').text();
            
            if (!shopId) {
                return;
            }
            
            // Show loading state
            const $select = $(this);
            $select.prop('disabled', true);
            
            // Send AJAX request to save the shop
            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_save_shop',
                    nonce: wpwps.nonce,
                    shop_id: shopId,
                    shop_name: shopName
                },
                success: function(response) {
                    if (response.success) {
                        wpwpsToast.success(response.data.message, 'Shop Saved');
                    } else {
                        wpwpsToast.error(response.data.message, 'Error');
                    }
                },
                error: function() {
                    wpwpsToast.error(wpwps.i18n.ajax_error, 'Error');
                },
                complete: function() {
                    // Restore select state
                    $select.prop('disabled', false);
                }
            });
        });
    }
    
    // Handle webhook URL copying
    function setupWebhookCopy() {
        $('#wpwps-copy-webhook-url').on('click', function() {
            const webhookUrl = $('#webhook_url').val();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(webhookUrl).then(function() {
                    wpwpsToast.success(wpwps.i18n.copied, 'Webhook URL');
                }, function() {
                    wpwpsToast.error(wpwps.i18n.copy_failed, 'Error');
                });
            } else {
                // Fallback for older browsers
                const textarea = $('<textarea>').val(webhookUrl).appendTo('body').select();
                try {
                    document.execCommand('copy');
                    wpwpsToast.success(wpwps.i18n.copied, 'Webhook URL');
                } catch (err) {
                    wpwpsToast.error(wpwps.i18n.copy_failed, 'Error');
                }
                textarea.remove();
            }
        });
    }
    
    // Handle settings form submissions
    function setupSettingsForms() {
        // API settings form
        $('#wpwps-api-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.html();
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            $submitBtn.html('<span class="wpwps-spinner"></span> ' + wpwps.i18n.saving);
            
            // Get form data
            const formData = {
                api_endpoint: $('#api_endpoint').val(),
                api_key: $('#api_key').val()
            };
            
            // Send AJAX request
            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_save_settings',
                    nonce: wpwps.nonce,
                    settings: formData
                },
                success: function(response) {
                    if (response.success) {
                        wpwpsToast.success(response.data.message, 'Settings');
                    } else {
                        wpwpsToast.error(response.data.message, 'Error');
                    }
                },
                error: function() {
                    wpwpsToast.error(wpwps.i18n.ajax_error, 'Error');
                },
                complete: function() {
                    // Restore button state
                    $submitBtn.prop('disabled', false);
                    $submitBtn.html(originalText);
                }
            });
        });
        
        // Other forms (using similar pattern)
        const forms = [
            '#wpwps-sync-settings-form',
            '#wpwps-email-queue-settings-form',
            '#wpwps-pop3-settings-form',
            '#wpwps-signature-settings-form',
            '#wpwps-openai-settings-form',
            '#wpwps-log-settings-form',
            '#wpwps-danger-zone-form'
        ];
        
        forms.forEach(function(formSelector) {
            $(formSelector).on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $submitBtn = $form.find('button[type="submit"]');
                const originalText = $submitBtn.html();
                
                // Show loading state
                $submitBtn.prop('disabled', true);
                $submitBtn.html('<span class="wpwps-spinner"></span> ' + wpwps.i18n.saving);
                
                // Get form data
                const formData = {};
                $form.find('input, select, textarea').each(function() {
                    const $input = $(this);
                    const name = $input.attr('name');
                    
                    if (!name) {
                        return;
                    }
                    
                    // Handle checkboxes
                    if ($input.attr('type') === 'checkbox') {
                        formData[name] = $input.is(':checked') ? 1 : 0;
                    } else {
                        formData[name] = $input.val();
                    }
                });
                
                // Send AJAX request
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_save_settings',
                        nonce: wpwps.nonce,
                        settings: formData
                    },
                    success: function(response) {
                        if (response.success) {
                            wpwpsToast.success(response.data.message, 'Settings');
                        } else {
                            wpwpsToast.error(response.data.message, 'Error');
                        }
                    },
                    error: function() {
                        wpwpsToast.error(wpwps.i18n.ajax_error, 'Error');
                    },
                    complete: function() {
                        // Restore button state
                        $submitBtn.prop('disabled', false);
                        $submitBtn.html(originalText);
                    }
                });
            });
        });
    }
    
    // Handle reset plugin data button
    function setupResetButton() {
        $('#wpwps-reset-plugin').on('click', function() {
            if (confirm(wpwps.i18n.confirm_reset)) {
                const $btn = $(this);
                const originalText = $btn.html();
                
                // Show loading state
                $btn.prop('disabled', true);
                $btn.html('<span class="wpwps-spinner"></span> ' + wpwps.i18n.resetting);
                
                // Send AJAX request
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_reset_plugin',
                        nonce: wpwps.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            wpwpsToast.success(response.data.message, 'Reset');
                            // Reload the page after a short delay
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            wpwpsToast.error(response.data.message, 'Error');
                            
                            // Restore button state
                            $btn.prop('disabled', false);
                            $btn.html(originalText);
                        }
                    },
                    error: function() {
                        wpwpsToast.error(wpwps.i18n.ajax_error, 'Error');
                        
                        // Restore button state
                        $btn.prop('disabled', false);
                        $btn.html(originalText);
                    }
                });
            }
        });
    }
    
    // Format number with commas
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Show/hide password fields
    function setupPasswordToggles() {
        $('.toggle-password').on('click', function() {
            const targetId = $(this).data('toggle');
            const $input = $('#' + targetId);
            const type = $input.attr('type') === 'password' ? 'text' : 'password';
            
            $input.attr('type', type);
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });
    }
    
    // Display temperature value
    function setupTemperatureRange() {
        $('#openai_temperature').on('input', function() {
            $('#temperature-value').text($(this).val());
        });
    }
    
    // Initialize
    $(document).ready(function() {
        // Set up API testing
        setupAPITesting();
        
        // Set up OpenAI testing
        setupOpenAITesting();
        
        // Set up shop selection
        setupShopSelection();
        
        // Set up webhook URL copying
        setupWebhookCopy();
        
        // Set up forms
        setupSettingsForms();
        
        // Set up password toggles
        setupPasswordToggles();
        
        // Set up temperature range display
        setupTemperatureRange();
        
        // Set up reset button
        setupResetButton();
    });
    
})(jQuery);
