jQuery(document).ready(function($) {
    // Utility function to show alerts with improved overflow handling
    function showAlert(message, type = 'info') {
        // Sanitize the message to prevent XSS
        const sanitizedMessage = $('<div>').text(message).html();
        
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${type === 'success' ? '<i class="fas fa-check-circle me-2"></i>' : ''}
                ${type === 'danger' ? '<i class="fas fa-exclamation-circle me-2"></i>' : ''}
                ${type === 'warning' ? '<i class="fas fa-exclamation-triangle me-2"></i>' : ''}
                ${type === 'info' ? '<i class="fas fa-info-circle me-2"></i>' : ''}
                <span class="alert-message">${sanitizedMessage}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        $('#alerts-container').append(alertHtml);
        
        // Make sure the alerts container is visible
        $('#alerts-container').show();
        
        // Only scroll if the alert is not in the viewport
        const $newAlert = $('#alerts-container .alert:last-child');
        const alertTop = $newAlert.offset().top;
        const viewportTop = $(window).scrollTop();
        const viewportBottom = viewportTop + $(window).height();
        
        if (alertTop < viewportTop || alertTop > viewportBottom) {
            $('html, body').animate({
                scrollTop: Math.max(0, alertTop - 100)
            }, 200);
        }
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            if ($('#alerts-container .alert').length > 0) {
                $('#alerts-container .alert').first().alert('close');
            }
        }, 5000);
    }
    
    // Check if API credentials are set to enable buttons
    function checkCredentials() {
        const apiKey = $('#printify_api_key').val().trim();
        const endpoint = $('#printify_endpoint').val().trim();
        
        // Enable/disable test connection button
        if (apiKey && endpoint) {
            $('#test-connection').prop('disabled', false);
            $('#fetch-shops').prop('disabled', false);
        } else {
            $('#test-connection').prop('disabled', true);
            $('#fetch-shops').prop('disabled', true);
        }
    }
    
    // Check credentials on input change
    $('#printify_api_key, #printify_endpoint').on('input', checkCredentials);
    
    // Initial check
    checkCredentials();
    
    // Save settings
    $('#printify-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'save_settings',
                nonce: wpwps_data.nonce,
                form_data: formData
            },
            beforeSend: function() {
                $('#save-settings').html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    showAlert('Settings saved successfully!', 'success');
                } else {
                    showAlert(response.data.message || 'Error saving settings', 'danger');
                }
            },
            error: function() {
                showAlert('An error occurred while saving settings.', 'danger');
            },
            complete: function() {
                $('#save-settings').html('<i class="fas fa-save"></i> Save Settings');
                checkCredentials();
            }
        });
    });
    
    // Test connection
    $('#test-connection').on('click', function() {
        const apiKey = $('#printify_api_key').val();
        const endpoint = $('#printify_endpoint').val();
        
        if (!apiKey || !endpoint) {
            showAlert('Please enter API Key and Endpoint before testing connection.', 'warning');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'test_connection',
                nonce: wpwps_data.nonce,
                api_key: apiKey,
                endpoint: endpoint
            },
            beforeSend: function() {
                $('#test-connection').html('<i class="fas fa-spinner fa-spin"></i> Testing...');
            },
            success: function(response) {
                if (response.success) {
                    showAlert('Connection successful! Your API credentials are valid.', 'success');
                } else {
                    showAlert(response.data.message || 'Connection failed', 'danger');
                }
            },
            error: function() {
                showAlert('An error occurred while testing connection.', 'danger');
            },
            complete: function() {
                $('#test-connection').html('<i class="fas fa-wifi"></i> Test Connection');
            }
        });
    });
    
    // Fetch shops
    $('#fetch-shops').on('click', function() {
        const apiKey = $('#printify_api_key').val();
        const endpoint = $('#printify_endpoint').val();
        
        if (!apiKey || !endpoint) {
            showAlert('Please enter API Key and Endpoint before fetching shops.', 'warning');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'fetch_shops',
                nonce: wpwps_data.nonce,
                api_key: apiKey,
                endpoint: endpoint
            },
            beforeSend: function() {
                $('#fetch-shops').html('<i class="fas fa-spinner fa-spin"></i> Fetching...');
                $('#shops-table tbody').html('<tr><td colspan="4" class="text-center">Loading shops...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    // Clear existing rows
                    $('#shops-table tbody').empty();
                    
                    // Show container
                    $('#shops-container').show();
                    
                    if (response.data.shops && response.data.shops.length > 0) {
                        // Add each shop to the table
                        $.each(response.data.shops, function(index, shop) {
                            const row = `
                                <tr>
                                    <td>${shop.id}</td>
                                    <td>${shop.title}</td>
                                    <td>${shop.connection_type || 'N/A'}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary select-shop" data-shop-id="${shop.id}" data-shop-title="${shop.title}">
                                            <i class="fas fa-check"></i> Select
                                        </button>
                                    </td>
                                </tr>
                            `;
                            $('#shops-table tbody').append(row);
                        });
                        
                        showAlert('Shops loaded successfully!', 'success');
                    } else {
                        $('#shops-table tbody').html('<tr><td colspan="4" class="text-center">No shops found</td></tr>');
                        showAlert('No shops found for this account.', 'info');
                    }
                } else {
                    $('#shops-container').hide();
                    showAlert(response.data.message || 'Failed to fetch shops', 'danger');
                }
            },
            error: function() {
                $('#shops-container').hide();
                showAlert('An error occurred while fetching shops.', 'danger');
            },
            complete: function() {
                $('#fetch-shops').html('<i class="fas fa-store"></i> Fetch Shops');
            }
        });
    });
    
    // Improve shop selection handler
    $(document).on('click', '.select-shop', function() {
        const shopId = $(this).data('shop-id');
        const shopTitle = $(this).data('shop-title');
        
        // Truncate shop title if too long for the dialog
        const truncatedTitle = shopTitle.length > 30 ? 
            shopTitle.substring(0, 27) + '...' : 
            shopTitle;
        
        // Confirm selection
        if (confirm(`Are you sure you want to set "${truncatedTitle}" as your default shop? This cannot be changed later.`)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'printify_sync',
                    action_type: 'select_shop',
                    nonce: wpwps_data.nonce,
                    shop_id: shopId,
                    shop_title: shopTitle
                },
                beforeSend: function() {
                    $('.select-shop').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        // Update the shop ID field
                        $('#printify_shop_id').val(shopId).attr('readonly', true);
                        
                        // Update UI
                        $('#shops-container').hide();
                        $('#fetch-shops').hide();
                        
                        showAlert(`Shop "${shopTitle}" has been set as your default shop.`, 'success');
                        
                        // Submit form to save the shop ID
                        $('#printify-settings-form').submit();
                    } else {
                        showAlert(response.data.message || 'Failed to select shop', 'danger');
                        $('.select-shop').prop('disabled', false);
                    }
                },
                error: function() {
                    showAlert('An error occurred while selecting the shop.', 'danger');
                    $('.select-shop').prop('disabled', false);
                }
            });
        }
    });
    
    // Manual sync
    $('#manual-sync').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'manual_sync',
                nonce: wpwps_data.nonce
            },
            beforeSend: function() {
                $('#manual-sync').html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
            },
            success: function(response) {
                if (response.success) {
                    showAlert(response.data.message || 'Sync completed successfully', 'success');
                    
                    // Update sync status info
                    $('#last-sync').text(response.data.last_sync);
                    $('#products-synced').text(response.data.products_synced);
                    $('#next-sync').text(response.data.next_sync);
                } else {
                    showAlert(response.data.message || 'Sync failed', 'danger');
                }
            },
            error: function() {
                showAlert('An error occurred while initiating sync.', 'danger');
            },
            complete: function() {
                $('#manual-sync').html('<i class="fas fa-sync-alt"></i> Run Manual Sync');
            }
        });
    });
    
    // Manual sync orders
    $('#manual-sync-orders').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'manual_sync_orders',
                nonce: wpwps_data.nonce
            },
            beforeSend: function() {
                $('#manual-sync-orders').html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
            },
            success: function(response) {
                if (response.success) {
                    showAlert(response.data.message || 'Orders sync completed successfully', 'success');
                    
                    // Update sync status info
                    $('#last-orders-sync').text(response.data.last_sync);
                    $('#orders-synced').text(response.data.orders_synced);
                } else {
                    showAlert(response.data.message || 'Sync failed', 'danger');
                }
            },
            error: function() {
                showAlert('An error occurred while initiating orders sync.', 'danger');
            },
            complete: function() {
                $('#manual-sync-orders').html('<i class="fas fa-sync-alt"></i> Sync Orders');
            }
        });
    });
});
