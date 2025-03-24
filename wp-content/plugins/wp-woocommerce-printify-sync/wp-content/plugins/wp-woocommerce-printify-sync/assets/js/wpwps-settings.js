(function($) {
    'use strict';

    const WPWPSSettings = {
        init: function() {
            this.initPrintifyHandlers();
            this.initOpenAIHandlers();
            this.initFormSubmitHandlers();
        },

        initPrintifyHandlers: function() {
            // Test Printify API connection
            $('#wpwps-test-printify').on('click', function() {
                const apiKey = $('#printify_api_key').val();
                const apiEndpoint = $('#printify_api_endpoint').val();
                
                if (!apiKey) {
                    $('#wpwps-printify-test-result').html('<span class="text-danger">API key is required</span>');
                    return;
                }
                
                $(this).attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
                $('#wpwps-printify-test-result').html('');
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_test_printify_connection',
                        nonce: wpwps.nonce,
                        api_key: apiKey,
                        api_endpoint: apiEndpoint
                    },
                    success: function(response) {
                        $('#wpwps-test-printify').attr('disabled', false).html('<i class="fas fa-sync-alt"></i> Test Connection');
                        
                        if (response.success) {
                            $('#wpwps-printify-test-result').html('<span class="text-success">' + response.data.message + '</span>');
                            
                            // Populate shops dropdown
                            if (response.data.shops && response.data.shops.length > 0) {
                                const $shopSelect = $('#printify_shop_id');
                                $shopSelect.empty();
                                $shopSelect.append($('<option>', {
                                    value: '',
                                    text: '-- Select a shop --'
                                }));
                                
                                $.each(response.data.shops, function(i, shop) {
                                    $shopSelect.append($('<option>', {
                                        value: shop.id,
                                        text: shop.title
                                    }));
                                });
                                
                                $('#printify-shops-container').show();
                            }
                        } else {
                            $('#wpwps-printify-test-result').html('<span class="text-danger">' + response.data.message + '</span>');
                        }
                    },
                    error: function() {
                        $('#wpwps-test-printify').attr('disabled', false).html('<i class="fas fa-sync-alt"></i> Test Connection');
                        $('#wpwps-printify-test-result').html('<span class="text-danger">Connection error</span>');
                    }
                });
            });
        },

        initOpenAIHandlers: function() {
            // Test OpenAI API connection
            $('#wpwps-test-openai').on('click', function() {
                const apiKey = $('#openai_api_key').val();
                const tokenLimit = $('#openai_token_limit').val();
                const temperature = $('#openai_temperature').val();
                const spendCap = $('#openai_spend_cap').val();
                
                if (!apiKey) {
                    $('#wpwps-openai-test-result').html('<span class="text-danger">API key is required</span>');
                    return;
                }
                
                $(this).attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
                $('#wpwps-openai-test-result').html('');
                $('#openai-cost-estimate').hide();
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_test_openai',
                        nonce: wpwps.nonce,
                        api_key: apiKey,
                        token_limit: tokenLimit,
                        temperature: temperature,
                        spend_cap: spendCap
                    },
                    success: function(response) {
                        $('#wpwps-test-openai').attr('disabled', false).html('<i class="fas fa-sync-alt"></i> Test Connection & Estimate Cost');
                        
                        if (response.success) {
                            $('#wpwps-openai-test-result').html('<span class="text-success">' + response.data.message + '</span>');
                            
                            // Show cost estimate
                            $('#openai-cost-estimate').html(
                                '<strong>Estimated Monthly Cost:</strong> ' + response.data.estimated_monthly_cost +
                                '<br><small>Based on an average of 10 support tickets per day using GPT-3.5-Turbo. ' +
                                'Your actual usage may vary.</small>'
                            ).show();
                        } else {
                            $('#wpwps-openai-test-result').html('<span class="text-danger">' + response.data.message + '</span>');
                        }
                    },
                    error: function() {
                        $('#wpwps-test-openai').attr('disabled', false).html('<i class="fas fa-sync-alt"></i> Test Connection & Estimate Cost');
                        $('#wpwps-openai-test-result').html('<span class="text-danger">Connection error</span>');
                    }
                });
            });
        },

        initFormSubmitHandlers: function() {
            // Save Printify settings
            $('#wpwps-printify-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = $(this).serialize();
                const $submitBtn = $(this).find('button[type="submit"]');
                
                $submitBtn.attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_save_settings',
                        nonce: wpwps.nonce,
                        ...WPWPSSettings.serializeFormToObject($(this))
                    },
                    success: function(response) {
                        $submitBtn.attr('disabled', false).html('Save Settings');
                        
                        if (response.success) {
                            WPWPSSettings.showNotice('success', response.data.message);
                        } else {
                            WPWPSSettings.showNotice('error', response.data.message);
                        }
                    },
                    error: function() {
                        $submitBtn.attr('disabled', false).html('Save Settings');
                        WPWPSSettings.showNotice('error', 'Error saving settings');
                    }
                });
            });
            
            // Save OpenAI settings
            $('#wpwps-openai-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                const $submitBtn = $(this).find('button[type="submit"]');
                
                $submitBtn.attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_save_settings',
                        nonce: wpwps.nonce,
                        ...WPWPSSettings.serializeFormToObject($(this))
                    },
                    success: function(response) {
                        $submitBtn.attr('disabled', false).html('Save Settings');
                        
                        if (response.success) {
                            WPWPSSettings.showNotice('success', response.data.message);
                        } else {
                            WPWPSSettings.showNotice('error', response.data.message);
                        }
                    },
                    error: function() {
                        $submitBtn.attr('disabled', false).html('Save Settings');
                        WPWPSSettings.showNotice('error', 'Error saving settings');
                    }
                });
            });
        },
        
        serializeFormToObject: function($form) {
            const formArray = $form.serializeArray();
            const obj = {};
            
            $.each(formArray, function(_, item) {
                obj[item.name] = item.value;
            });
            
            return obj;
        },
        
        showNotice: function(type, message) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap.wpwps-settings-page h1').after($notice);
            
            // Auto dismiss after 3 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    $(document).ready(function() {
        WPWPSSettings.init();
    });

})(jQuery);
