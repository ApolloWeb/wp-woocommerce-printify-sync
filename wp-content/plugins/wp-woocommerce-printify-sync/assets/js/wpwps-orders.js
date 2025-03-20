jQuery(document).ready(function($) {
    let currentPage = 1;
    const perPage = 10;

    function fetchOrders(page = 1, refreshCache = false) {
        currentPage = page;
        const button = $('#fetch-orders');
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        $('#orders-table tbody').html('<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading orders from Printify...</td></tr>');

        console.log('Fetching orders page:', page, 'refresh cache:', refreshCache);

        // Use GET method for fetching orders
        $.ajax({
            url: ajaxurl || wpwps_data.ajax_url,
            type: 'GET',
            data: {
                action: 'printify_sync',
                action_type: 'fetch_printify_orders',
                nonce: wpwps_data.nonce,
                page: page,
                per_page: perPage,
                refresh_cache: refreshCache ? 'true' : 'false'
            },
            success: function(response) {
                console.log('Orders API response:', response);
                if (response.success) {
                    updateOrdersTable(response.data);
                } else {
                    handleError(response.data?.message || 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error, xhr.responseText);
                try {
                    const response = JSON.parse(xhr.responseText);
                    handleError('Failed to fetch orders: ' + (response.data?.message || error));
                } catch (e) {
                    handleError('Failed to fetch orders: ' + error);
                }
            },
            complete: function() {
                button.prop('disabled', false).html(originalHtml);
            }
        });
    }

    // Event handlers
    $('#fetch-orders').on('click', function() {
        fetchOrders(1, true);
    });

    $('#clear-cache').on('click', function() {
        fetchOrders(1, true);
    });

    // Helper functions
    function updateOrdersTable(data) {
        const orders = data.orders || [];
        const tbody = $('#orders-table tbody');
        tbody.empty();

        if (orders.length === 0) {
            tbody.html('<tr><td colspan="8" class="text-center">No orders found</td></tr>');
            return;
        }

        orders.forEach(function(order) {
            const isImported = order.is_imported;
            const importButtonHtml = isImported ? 
                `<button class="btn btn-sm btn-success import-order" disabled data-id="${order.printify_id}"><i class="fas fa-check"></i> Imported</button>` : 
                `<button class="btn btn-sm btn-primary import-order" data-id="${order.printify_id}"><i class="fas fa-download"></i> Import</button>`;

            const row = `
                <tr${isImported ? ' class="bg-light"' : ''}>
                    <td>${order.printify_id}</td>
                    <td>${order.woo_order_id || 'N/A'}</td>
                    <td>${order.date}</td>
                    <td>${order.customer}</td>
                    <td><span class="badge bg-${order.status === 'active' ? 'success' : 'secondary'}">${order.status}</span></td>
                    <td>${order.total}</td>
                    <td>${order.shipping_status}</td>
                    <td>${importButtonHtml}</td>
                </tr>
            `;
            tbody.append(row);
        });

        // Update pagination info
        $('#showing-start').text((data.current_page - 1) * data.per_page + 1);
        $('#showing-end').text(Math.min(data.current_page * data.per_page, data.total));
        $('#total-orders').text(data.total);

        // Setup pagination
        setupPagination(data.current_page, data.last_page);
    }

    // Setup pagination
    function setupPagination(currentPage, lastPage) {
        const pagination = $('#orders-pagination');
        pagination.empty();

        if (lastPage <= 1) {
            return;
        }

        // Previous button
        pagination.append(`
            <li class="page-item${currentPage === 1 ? ' disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `);

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(lastPage, startPage + 4);

        for (let i = startPage; i <= endPage; i++) {
            pagination.append(`
                <li class="page-item${i === currentPage ? ' active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        // Next button
        pagination.append(`
            <li class="page-item${currentPage === lastPage ? ' disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `);

        // Add click handler to pagination links
        $('.page-link').on('click', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && page !== currentPage) {
                fetchOrders(page, false);
            }
        });
    }

    function handleError(message) {
        $('#orders-table tbody').html(`
            <tr>
                <td colspan="8" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle"></i> ${message}
                </td>
            </tr>
        `);
    }
});
