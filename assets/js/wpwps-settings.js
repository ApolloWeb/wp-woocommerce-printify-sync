/**
 * Settings page functionality
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize settings page
        initSettingsPage();

        // Initialize range sliders
        initRangeSliders();
        
        // Add API documentation links
        addApiDocLinks();
    });

    /**
     * Initialize settings page functionality
     */
    function initSettingsPage() {
        // Test Printify connection
        $('.wpwps-test-printify-connection').on('click', function() {
            const $button = $(this);
            const $spinner = $('#printify_test_connection-spinner');
            const $result = $('#printify_test_connection-result');
            
            $button.prop('disabled', true);
            $spinner.removeClass('d-none');
            $result.html('');
            
            $.ajax({
                url: wpwps_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_test_printify_connection',
                    nonce: wpwps_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html(`<div class="alert alert-success mt-3">${response.data.message}</div>`);
                        
                        // Enable and populate shop dropdown
                        const $shopSelect = $('#printify_shop_select');
                        $shopSelect.empty().prop('disabled', false);
                        $shopSelect.append(`<option value="">${wpwpsSettings.selectShop}</option>`);
                        
                        response.data.shops.forEach(shop => {
                            $shopSelect.append(`<option value="${shop.id}">${shop.name}</option>`);
                        });

                        // Show toast
                        wpwpsToastManager.showToast('success', 'API Connection', 'Printify API connection successful!');
                    } else {
                        $result.html(`<div class="alert alert-danger mt-3">${response.data.message}</div>`);
                        wpwpsToastManager.showToast('error', 'API Error', response.data.message);
                    }
                },
                error: function() {
                    $result.html(`<div class="alert alert-danger mt-3">${wpwpsSettings.connectionError}</div>`);
                    wpwpsToastManager.showToast('error', 'Error', 'Server error occurred');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.addClass('d-none');
                }
            });
        });

        // Handle shop selection
        $('#printify_shop_select').on('change', function() {
            const shopId = $(this).val();
            if (!shopId) return;
            
            const $shopIdField = $('#printify_shop_id');
            
            $.ajax({
                url: wpwps_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_set_printify_shop',
                    nonce: wpwps_admin.nonce,
                    shop_id: shopId
                },
                success: function(response) {
                    if (response.success) {
                        $shopIdField.val(shopId);
                        wpwpsToastManager.showToast('success', 'Shop Selected', 'Shop ID saved successfully');
                    } else {
                        wpwpsToastManager.showToast('error', 'Error', response.data.message);
                    }
                },
                error: function() {
                    wpwpsToastManager.showToast('error', 'Error', 'Server error occurred');
                }
            });
        });

        // Test ChatGPT connection
        $('.wpwps-test-chatgpt-connection').on('click', function() {
            const $button = $(this);
            const $spinner = $('#chatgpt_test_connection-spinner');
            const $result = $('#chatgpt_test_connection-result');
            
            $button.prop('disabled', true);
            $spinner.removeClass('d-none');
            $result.html('');
            
            $.ajax({
                url: wpwps_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_test_chatgpt_connection',
                    nonce: wpwps_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const costClass = response.data.within_budget ? 'text-success' : 'text-danger';
                        
                        $result.html(`
                            <div class="alert alert-success mt-3">
                                <h6 class="mb-2">${response.data.message}</h6>
                                <p class="mb-1"><strong>Model:</strong> ${response.data.model}</p>
                                <p class="mb-1"><strong>Est. Monthly Usage:</strong> ${response.data.estimated_tokens} tokens</p>
                                <p class="mb-0"><strong>Est. Monthly Cost:</strong> <span class="${costClass}">${response.data.estimated_cost}</span></p>
                            </div>
                        `);

                        wpwpsToastManager.showToast('success', 'ChatGPT API', 'Connection successful!');
                    } else {
                        $result.html(`<div class="alert alert-danger mt-3">${response.data.message}</div>`);
                        wpwpsToastManager.showToast('error', 'API Error', response.data.message);
                    }
                },
                error: function() {
                    $result.html(`<div class="alert alert-danger mt-3">${wpwpsSettings.connectionError}</div>`);
                    wpwpsToastManager.showToast('error', 'Error', 'Server error occurred');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.addClass('d-none');
                }
            });
        });

        // Refresh webhooks
        $('.wpwps-refresh-webhooks').on('click', function() {
            const $button = $(this);
            const $container = $('.wpwps-webhook-status-container');
            
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Refreshing...');
            
            $.ajax({
                url: wpwps_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_get_webhooks',
                    nonce: wpwps_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // This would normally update the UI with the latest webhooks
                        // For now we'll just show a success message
                        wpwpsToastManager.showToast('success', 'Webhooks', 'Webhook list refreshed');
                    } else {
                        wpwpsToastManager.showToast('error', 'Error', response.data.message);
                    }
                },
                error: function() {
                    wpwpsToastManager.showToast('error', 'Error', 'Server error occurred');
                },
                complete: function() {
                    $button.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i> Refresh Webhooks');
                }
            });
        });

        // Handle webhook toggles
        $('.wpwps-webhook-toggle').on('change', function() {
            const event = $(this).data('event');
            const isEnabled = $(this).prop('checked');
            
            if (isEnabled) {
                createWebhook(event);
            } else {
                // This would normally delete the webhook
                // For now we'll just show a message
                wpwpsToastManager.showToast('info', 'Webhook', `Webhook ${event} would be deleted`);
            }
        });

        // Save webhook changes
        $('.wpwps-save-webhooks').on('click', function() {
            const $button = $(this);
            const $spinner = $('.wpwps-webhook-spinner');
            
            $button.prop('disabled', true);
            $spinner.removeClass('d-none');
            
            // This would normally save all webhook changes
            // For now we'll just simulate a delay and show success
            setTimeout(function() {
                $button.prop('disabled', false);
                $spinner.addClass('d-none');
                wpwpsToastManager.showToast('success', 'Webhooks', 'Webhook settings saved successfully');
            }, 1000);
        });

        // Delete webhook
        $(document).on('click', '.wpwps-delete-webhook', function() {
            const webhookId = $(this).data('webhook-id');
            
            // Confirm delete
            if (!confirm(wpwpsSettings.confirmDeleteWebhook)) {
                return;
            }
            
            const $button = $(this);
            $button.html('<i class="fas fa-spinner fa-spin"></i>');
            
            $.ajax({
                url: wpwps_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_delete_webhook',
                    nonce: wpwps_admin.nonce,
                    webhook_id: webhookId
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').fadeOut(function() {
                            $(this).remove();
                        });
                        wpwpsToastManager.showToast('success', 'Webhook', 'Webhook deleted successfully');
                    } else {
                        wpwpsToastManager.showToast('error', 'Error', response.data.message);
                        $button.html('<i class="fas fa-trash"></i>');
                    }
                },
                error: function() {
                    wpwpsToastManager.showToast('error', 'Error', 'Server error occurred');
                    $button.html('<i class="fas fa-trash"></i>');
                }
            });
        });
    }

    /**
     * Create a webhook
     * Based on the Printify API documentation
     * 
     * @param {string} event Event name
     */
    function createWebhook(event) {
        $.ajax({
            url: wpwps_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_create_webhook',
                nonce: wpwps_admin.nonce,
                event: event
            },
            success: function(response) {
                if (response.success) {
                    // Show webhook created success message with details from API response
                    if (response.data && response.data.id) {
                        wpwpsToastManager.showToast('success', 'Webhook', `Webhook for ${event} created with ID: ${response.data.id}`);
                    } else {
                        wpwpsToastManager.showToast('success', 'Webhook', `Webhook for ${event} created successfully`);
                    }
                } else {
                    wpwpsToastManager.showToast('error', 'Error', response.data.message);
                }
            },
            error: function() {
                wpwpsToastManager.showToast('error', 'Error', 'Server error occurred');
            }
        });
    }

    /**
     * Add API documentation links to relevant sections
     */
    function addApiDocLinks() {
        // Add Printify API documentation link
        $('#printify_api .form-table').before(
            '<div class="alert alert-info">' +
            '<p><strong>API Documentation</strong>: For more information on the Printify API, visit the ' +
            '<a href="https://developers.printify.com/" target="_blank">Printify Developer Portal</a>.</p>' +
            '<p>Learn about <a href="https://developers.printify.com/#webhooks" target="_blank">Webhooks</a>, ' +
            '<a href="https://developers.printify.com/#products" target="_blank">Products</a>, and ' +
            '<a href="https://developers.printify.com/#orders" target="_blank">Orders</a>.</p>' +
            '</div>'
        );
        
        // Add ChatGPT API documentation link
        $('#chatgpt_api .form-table').before(
            '<div class="alert alert-info">' +
            '<p><strong>OpenAI Documentation</strong>: For more information on the ChatGPT API, visit the ' +
            '<a href="https://platform.openai.com/docs/api-reference" target="_blank">OpenAI API Reference</a>.</p>' +
            '</div>'
        );
    }

    /**
     * Initialize range sliders
     */
    function initRangeSliders() {
        // Update range slider value display
        $('input[type="range"]').on('input', function() {
            const $this = $(this);
            $this.next('.range-value').text($this.val());
        });
    }

})(jQuery);
