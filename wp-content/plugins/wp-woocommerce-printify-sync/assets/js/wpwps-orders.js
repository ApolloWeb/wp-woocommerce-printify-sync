jQuery(document).ready(function($) {
    const perPage = 50; // Increase per page to maximum

    // Event handlers
    $('#fetch-orders').on('click', function() {
        fetchOrders(true);
    });

    $('#clear-cache').on('click', function() {
        fetchOrders(true);
    });
    
    // New import all orders button handler
    $('#import-all-orders').on('click', function() {
        importAllOrders();
    });

    // Initialize import button handlers
    $(document).on('click', '.import-order', function() {
        const button = $(this);
        const orderId = button.data('id');
        const originalHtml = button.html();
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: ajaxurl || wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'import_order_to_woo',
                nonce: wpwps_data.nonce,
                printify_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    button.html('<i class="fas fa-check"></i> Imported').addClass('btn-success').removeClass('btn-primary');
                    button.closest('tr').addClass('bg-light');
                } else {
                    button.html('<i class="fas fa-times"></i> Failed').addClass('btn-danger').removeClass('btn-primary');
                    setTimeout(function() {
                        button.prop('disabled', false).html(originalHtml).removeClass('btn-danger').addClass('btn-primary');
                    }, 3000);
                }
            },
            error: function() {
                button.html('<i class="fas fa-times"></i> Error').addClass('btn-danger').removeClass('btn-primary');
                setTimeout(function() {
                    button.prop('disabled', false).html(originalHtml).removeClass('btn-danger').addClass('btn-primary');
                }, 3000);
            }
        });
    });

    function fetchOrders(refreshCache = false) {
        const button = $('#fetch-orders');
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        $('#orders-table tbody').html('<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading orders from Printify...</td></tr>');

        $.ajax({
            url: ajaxurl || wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'fetch_printify_orders',
                nonce: wpwps_data.nonce,
                page: 1,
                per_page: perPage,
                refresh_cache: refreshCache ? 'true' : 'false'
            },
            success: function(response) {
                console.log('Orders API response:', response);
                if (response && response.success && response.data) {
                    updateOrdersTable(response.data);
                    
                    // Enable the import all button if we have orders
                    if (response.data.orders && response.data.orders.length > 0) {
                        $('#import-all-orders').prop('disabled', false);
                    } else {
                        $('#import-all-orders').prop('disabled', true);
                    }
                } else {
                    const errorMsg = response?.data?.message || 'Unknown error occurred';
                    handleError(errorMsg);
                    $('#import-all-orders').prop('disabled', true);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error, xhr.responseText);
                handleError('Failed to fetch orders: ' + error);
                $('#import-all-orders').prop('disabled', true);
            },
            complete: function() {
                button.prop('disabled', false).html(originalHtml);
            }
        });
    }
    
    // New function to import all orders
    function importAllOrders() {
        if (!confirm('Are you sure you want to import all orders from Printify to WooCommerce?')) {
            return;
        }
        
        const button = $('#import-all-orders');
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing All...');
        
        // Get all non-imported order IDs
        const orderIds = [];
        $('.import-order:not(.btn-success)').each(function() {
            orderIds.push($(this).data('id'));
        });
        
        if (orderIds.length === 0) {
            alert('No orders to import! All orders have already been imported.');
            button.prop('disabled', false).html(originalHtml);
            return;
        }
        
        $.ajax({
            url: ajaxurl || wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'bulk_import_orders',
                nonce: wpwps_data.nonce,
                printify_ids: orderIds
            },
            success: function(response) {
                if (response.success) {
                    // Mark imported orders in the UI
                    response.data.imported.forEach(function(item) {
                        const row = $(`.import-order[data-id="${item.printify_id}"]`).closest('tr');
                        row.find('.import-order').prop('disabled', true)
                            .html('<i class="fas fa-check"></i> Imported')
                            .addClass('btn-success')
                            .removeClass('btn-primary');
                        row.addClass('bg-light');
                    });
                    
                    // Show success message
                    const message = `Successfully imported ${response.data.imported.length} orders. ${response.data.failed.length} orders failed.`;
                    $('#orders-alerts').html(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> ${message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    
                    if (response.data.failed.length > 0) {
                        console.error('Failed orders:', response.data.failed);
                    }
                } else {
                    $('#orders-alerts').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> ${response.data.message || 'Unknown error occurred'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                $('#orders-alerts').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> Failed to import orders: ${error}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            },
            complete: function() {
                button.prop('disabled', false).html(originalHtml);
                
                // If all orders imported, disable the button
                if ($('.import-order:not(.btn-success)').length === 0) {
                    button.prop('disabled', true);
                }
            }
        });
    }

    function updateOrdersTable(data) {
        const orders = data.orders || [];
        const tbody = $('#orders-table tbody');
        tbody.empty();

        if (orders.length === 0) {
            tbody.html('<tr><td colspan="8" class="text-center">No orders found</td></tr>');
            
            // Reset counters
            $('#showing-start').text('0');
            $('#showing-end').text('0');
            $('#total-orders').text('0');
            return;
        }

        orders.forEach(function(order) {
            const isImported = order.is_imported;
            const importButtonHtml = isImported ? 
                `<button class="btn btn-sm btn-success import-order" disabled data-id="${order.id}"><i class="fas fa-check"></i> Imported</button>` : 
                `<button class="btn btn-sm btn-primary import-order" data-id="${order.id}"><i class="fas fa-download"></i> Import</button>`;
                
            // Format customer name from address_to
            const firstName = order.address_to?.first_name || '';
            const lastName = order.address_to?.last_name || '';
            const customerName = (firstName || lastName) ? `${firstName} ${lastName}`.trim() : 'N/A';

            // Format status badge with appropriate color
            const statusBadgeClass = {
                'pending': 'warning',
                'fulfilled': 'success',
                'on-hold': 'info',
                'cancelled': 'danger',
                'refunded': 'secondary',
                'processing': 'primary'
            }[order.status] || 'secondary';

            // Format created date
            const orderDate = order.created_at ? new Date(order.created_at).toLocaleDateString() : 'N/A';

            // Format price (convert from cents to dollars)
            const price = order.total_price ? `$${(order.total_price/100).toFixed(2)}` : 'N/A';
            
            // Format shipping status
            let shippingStatus = 'Pending';
            if (order.shipments && order.shipments.length > 0) {
                const carrier = order.shipments[0].carrier || 'N/A';
                const trackingNumber = order.shipments[0].number || '';
                shippingStatus = trackingNumber ? `${carrier} - ${trackingNumber}` : carrier;
            }

            const row = `
                <tr${isImported ? ' class="bg-light"' : ''}>
                    <td>${order.id || 'N/A'}</td>
                    <td>${order.woo_order_id || 'N/A'}</td>
                    <td>${orderDate}</td>
                    <td>${customerName}</td>
                    <td><span class="badge bg-${statusBadgeClass}">${order.status || 'Unknown'}</span></td>
                    <td>${price}</td>
                    <td>${shippingStatus}</td>
                    <td>${importButtonHtml}</td>
                </tr>
            `;
            tbody.append(row);
        });

        // Update counters
        $('#showing-start').text('1');
        $('#showing-end').text(orders.length);
        $('#total-orders').text(data.total || orders.length);
    }

    function handleError(message) {
        $('#orders-table tbody').html(`
            <tr>
                <td colspan="8" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle"></i> ${message}
                </td>
            </tr>
        `);
        
        // Show alert
        $('#orders-alerts').html(`
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        
        // Reset counters
        $('#showing-start').text('0');
        $('#showing-end').text('0');
        $('#total-orders').text('0');
    }
});
