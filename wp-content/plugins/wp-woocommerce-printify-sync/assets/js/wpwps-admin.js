/**
 * Admin JavaScript for WP WooCommerce Printify Sync.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

(function($) {
    'use strict';

    // Global admin state
    const wpwpsAdmin = {
        init() {
            this.initToasts();
            this.initNavigation();
            this.initQuickActions();
            this.setupGlobalHandlers();
        },

        // Initialize toast notifications
        initToasts() {
            // Initialize toast container if doesn't exist
            if (!$('.wpwps-toast-container').length) {
                $('body').append('<div class="wpwps-toast-container"></div>');
            }
        },

        // Initialize navigation components
        initNavigation() {
            $('.wpwps-nav-item').on('click', function() {
                $('.wpwps-nav-item').removeClass('active');
                $(this).addClass('active');
            });
        },

        // Initialize quick action buttons 
        initQuickActions() {
            $('.wpwps-quick-action').on('click', function() {
                const action = $(this).data('action');
                wpwpsAdmin.handleQuickAction(action);
            });
        },

        // Setup global event handlers
        setupGlobalHandlers() {
            // Handle loading states
            $(document).on('click', '.wpwps-action-button', function() {
                const $btn = $(this);
                if (!$btn.hasClass('no-loading')) {
                    wpwpsAdmin.showLoading($btn);
                }
            });

            // Handle form submissions
            $(document).on('submit', '.wpwps-form', function(e) {
                e.preventDefault();
                const $form = $(this);
                wpwpsAdmin.handleFormSubmit($form);
            });
        },

        // Show loading state on element
        showLoading($el) {
            const $spinner = $('<span class="wpwps-spinner"></span>');
            const originalContent = $el.html();
            
            $el.prop('disabled', true)
               .data('original-content', originalContent)
               .html($spinner);
            
            return () => {
                $el.prop('disabled', false)
                   .html(originalContent);
            };
        },

        // Handle API requests
        async apiRequest(endpoint, data = {}, method = 'POST') {
            try {
                const response = await $.ajax({
                    url: wpwps.ajax_url,
                    type: method,
                    data: {
                        action: `wpwps_${endpoint}`,
                        nonce: wpwps.nonce,
                        ...data
                    }
                });

                return response;
            } catch (error) {
                console.error('API Request failed:', error);
                throw error;
            }
        }
    };

    // Initialize on document ready
    $(document).ready(() => wpwpsAdmin.init());

    // Export to window
    window.wpwpsAdmin = wpwpsAdmin;

    /**
     * Test API connection.
     */
    $('#wpwps-test-connection').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const statusSpan = $('#wpwps-connection-status');
        
        button.prop('disabled', true);
        statusSpan.html('<span class="spinner is-active" style="float:none;"></span> ' + wpwpsData.i18n.testingConnection);
        
        $.ajax({
            url: wpwpsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wpwps_test_api_connection',
                nonce: wpwpsData.nonce,
                api_key: $('#api_key').val()
            },
            success: function(response) {
                if (response.success) {
                    statusSpan.html('<span class="dashicons dashicons-yes" style="color:green;"></span> ' + response.data.message);
                } else {
                    statusSpan.html('<span class="dashicons dashicons-no" style="color:red;"></span> ' + response.data.message);
                }
            },
            error: function() {
                statusSpan.html('<span class="dashicons dashicons-no" style="color:red;"></span> ' + wpwpsData.i18n.connectionError);
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    /**
     * Sync products.
     */
    $('#wpwps-sync-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const button = $('#wpwps-sync-products');
        const spinner = $('#wpwps-sync-spinner');
        const resultsDiv = $('.wpwps-sync-results');
        const statusDiv = $('#wpwps-sync-status');
        const tbody = $('#wpwps-products-tbody');
        
        // Validate form
        if (!$('#wpwps-shop-id').val()) {
            return false;
        }
        
        // Show loading state
        button.prop('disabled', true);
        spinner.addClass('is-active');
        statusDiv.removeClass('notice-success notice-error').hide();
        tbody.empty();
        
        $.ajax({
            url: wpwpsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wpwps_sync_products',
                nonce: wpwpsData.nonce,
                shop_id: $('#wpwps-shop-id').val()
            },
            success: function(response) {
                if (response.success) {
                    statusDiv.addClass('notice-success').html('<p>' + response.data.message + '</p>').show();
                    
                    // Display synced products
                    if (response.data.products.length) {
                        $.each(response.data.products, function(index, product) {
                            const productObj = wc_get_product(product);
                            
                            if (productObj) {
                                const row = $('<tr></tr>');
                                row.append('<td>' + product + '</td>');
                                row.append('<td><a href="' + getProductEditUrl(product) + '">' + productObj.get_name() + '</a></td>');
                                row.append('<td>' + productObj.get_price_html() + '</td>');
                                row.append('<td>' + productObj.get_status() + '</td>');
                                tbody.append(row);
                            }
                        });
                    }
                    
                    resultsDiv.show();
                } else {
                    statusDiv.addClass('notice-error').html('<p>' + response.data.message + '</p>').show();
                }
            },
            error: function() {
                statusDiv.addClass('notice-error').html('<p>' + wpwpsData.i18n.syncError + '</p>').show();
            },
            complete: function() {
                button.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });

    /**
     * Helper function to get a product edit URL.
     *
     * @param {number} productId The product ID.
     * @return {string} The edit URL.
     */
    function getProductEditUrl(productId) {
        return wpwpsData.adminUrl + 'post.php?post=' + productId + '&action=edit';
    }
})(jQuery);
