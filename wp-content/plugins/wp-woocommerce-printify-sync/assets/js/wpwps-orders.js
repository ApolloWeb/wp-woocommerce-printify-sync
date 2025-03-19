jQuery(document).ready(function($) {
    let currentPage = 1;
    const perPage = 10;

    function showLoading(button) {
        button.prop('disabled', true)
              .html('<i class="fas fa-spinner fa-spin"></i> Loading...');
    }

    function hideLoading(button, originalHtml) {
        button.prop('disabled', false)
              .html(originalHtml);
    }

    function updatePagination(data) {
        const totalPages = Math.ceil(data.total / data.per_page);
        let paginationHtml = '';
        
        // Previous button
        paginationHtml += `
            <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${data.current_page - 1}">Previous</a>
            </li>
        `;
        
        // First page
        paginationHtml += `
            <li class="page-item ${data.current_page === 1 ? 'active' : ''}">
                <a class="page-link" href="#" data-page="1">1</a>
            </li>
        `;
        
        // Second page
        if (totalPages > 1) {
            paginationHtml += `
                <li class="page-item ${data.current_page === 2 ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="2">2</a>
                </li>
            `;
        }
        
        // Next button
        paginationHtml += `
            <li class="page-item ${data.current_page === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${data.current_page + 1}">Next</a>
            </li>
        `;
        
        $('#orders-pagination').html(paginationHtml);
        
        // Update count text
        const start = ((data.current_page - 1) * data.per_page) + 1;
        const end = Math.min(data.current_page * data.per_page, data.total);
        $('#showing-start').text(start);
        $('#showing-end').text(end);
        $('#total-orders').text(data.total);
    }

    function renderOrders(orders) {
        const tbody = $('#orders-table tbody');
        tbody.empty();
        
        if (orders.length === 0) {
            tbody.html('<tr><td colspan="8" class="text-center">No orders found</td></tr>');
            return;
        }
        
        orders.forEach(order => {
            const row = `
                <tr>
                    <td>${order.wc_order_id}</td>
                    <td>${order.printify_order_id}</td>
                    <td>${order.date}</td>
                    <td>${order.customer}</td>
                    <td>
                        <span class="badge bg-${order.status === 'completed' ? 'success' : 'secondary'}">
                            ${order.status}
                        </span>
                    </td>
                    <td>${order.total}</td>
                    <td>${order.shipping_status}</td>
                    <td>
                        <a href="/wp-admin/post.php?post=${order.wc_order_id}&action=edit" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function fetchOrders(page = 1) {
        currentPage = page;
        const button = $('#sync-orders');
        const originalHtml = button.html();
        showLoading(button);
        
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'fetch_printify_orders',
                nonce: wpwps_data.nonce,
                page: page,
                per_page: perPage
            },
            success: function(response) {
                if (response.success) {
                    renderOrders(response.data.orders);
                    updatePagination(response.data);
                } else {
                    alert(response.data.message || 'Failed to fetch orders');
                }
            },
            error: function() {
                alert('An error occurred while fetching orders');
            },
            complete: function() {
                hideLoading(button, originalHtml);
            }
        });
    }

    // Event Handlers
    $('#sync-orders').on('click', function() {
        fetchOrders(1);
    });

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && !isNaN(page) && !$(this).parent().hasClass('disabled')) {
            fetchOrders(parseInt(page));
        }
    });

    // Initial load
    fetchOrders(1);
});
