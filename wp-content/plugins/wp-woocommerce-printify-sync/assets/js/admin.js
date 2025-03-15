/**
 * Admin JavaScript for WP WooCommerce Printify Sync
 */
(function($) {
    'use strict';
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Initialize sync button functionality
        initSyncButton();
        
        // Initialize API key testing
        initApiKeyTesting();
        
        // Initialize settings form
        initSettingsForm();
    });
    
    /**
     * Initialize sync button
     */
    function initSyncButton() {
        const $syncButton = $('#wpps-sync-button');
        const $syncProgress = $('#wpps-sync-progress');
        const $syncResponse = $('#wpps-sync-response');
        
        if (!$syncButton.length) {
            return;
        }
        
        $syncButton.on('click', function(e) {
            e.preventDefault();
            
            // Disable button and show progress
            $syncButton.prop('disabled', true).text('Syncing...');
            $syncProgress.show();
            $syncResponse.html('');
            
            // Start progress animation
            let progress = 0;
            const $progressBar = $syncProgress.find('.wpps-progress-bar-inner');
            
            const progressInterval = setInterval(function() {
                progress += 5;
                if (progress >= 100) {
                    clearInterval(progressInterval);
                }
                $progressBar.css('width', progress + '%');
            }, 300);
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpps_sync_products',
                    nonce: wppsAdmin.nonce
                },
                success: function(response) {
                    clearInterval(progressInterval);
                    $progressBar.css('width', '100%');
                    
                    setTimeout(function() {
                        if (response.success) {
                            $syncResponse.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            $syncResponse.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                        
                        $syncButton.prop('disabled', false).text('Sync Products Now');
                        $syncProgress.hide();
                        $progressBar.css('width', '0%');
                    }, 500);
                },
                error: function() {
                    clearInterval(progressInterval);
                    $syncResponse.html('<div class="notice notice-error"><p>Failed to connect to server.</p></div>');
                    $syncButton.prop('disabled', false).text('Sync Products Now');
                    $syncProgress.hide();
                    $progressBar.css('width', '0%');
                }
            });
        });
    }
    
    /**
     * Initialize API key testing
     */
    function initApiKeyTesting() {
        const $testButtons = $('.wpps-test-api-button');
        
        if (!$testButtons.length) {
            return;
        }
        
        $testButtons.on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const apiType = $button.data('api');
            const $input = $('#wpps_' + apiType + '_api_key');
            const $result = $('#wpps-' + apiType + '-test-result');
            
            $button.prop('disabled', true).text('Testing...');
            $result.html('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpps_test_' + apiType + '_api',
                    api_key: $input.val(),
                    nonce: wppsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                    } else {
                        $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    }
                    
                    $button.prop('disabled', false).text('Test Connection');
                },
                error: function() {
                    $result.html('<div class="notice notice-error inline"><p>Failed to connect to server.</p></div>');
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        });
    }
    
    /**
     * Initialize settings form
     */
    function initSettingsForm() {
        const $form = $('.wpps-settings-form');
        
        if (!$form.length) {
            return;
        }
        
        $form.on('submit', function(e) {
            e.preventDefault();
            
            const $submitButton = $form.find('button[type="submit"]');
            const $message = $('#wpps-settings-message');
            
            $submitButton.prop('disabled', true).text(wppsAdmin.i18n.savingSettings);
            $message.html('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: $form.serialize() + '&action=wpps_save_settings&nonce=' + wppsAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        $message.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    } else {
                        $message.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                    
                    $submitButton.prop('disabled', false).text('Save Settings');
                },
                error: function() {
                    $message.html('<div class="notice notice-error"><p>Failed to connect to server.</p></div>');
                    $submitButton.prop('disabled', false).text('Save Settings');
                }
            });
        });
    }
    
})(jQuery);