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

    // Show alert messages
    function showAlert(message, type = 'info') {
        return `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    }

    // Clear cache on page load
    $.ajax({
        url: wpwps_data.ajax_url,
        type: 'POST',
        data: {
            action: 'printify_sync',
            action_type: 'clear_cache',
            nonce: wpwps_data.nonce
        },
        success: function(response) {
            if (response.success) {
                $('#orders-alerts').html(showAlert('Cache cleared. Click "Fetch Orders" to load fresh data.', 'info'));
            }
        }
    });

    // Show initial message in the table
    $('#orders-table tbody').html('<tr><td colspan="8" class="text-center">Click "Fetch Orders" to load orders from Printify</td></tr>');

    function fetchOrders(page = 1, refreshCache = false) {
        currentPage = page;
        const button = $('#fetch-orders');
        const originalHtml = button.html();
        showLoading(button);

        $('#orders-table tbody').html('<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading orders from Printify...</td></tr>');
        
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'GET',
            data: {
                action: 'printify_sync',
                action_type: 'fetch_printify_orders',
                nonce: wpwps_data.nonce,
                page: page,
                per_page: perPage,
                refresh_cache: refreshCache
            },
            success: function(response) {
                if (response.success) {
                    renderOrders(response.data.orders);
                    updatePagination(response.data);
                } else {
                    $('#orders-alerts').html(showAlert(response.data.message || 'Failed to fetch orders', 'danger'));
                }
            },
            error: function() {
                $('#orders-alerts').html(showAlert('Network error while fetching orders', 'danger'));
            },
            complete: function() {
                hideLoading(button, originalHtml);
            }
        });
    }

    // Event Handlers
    $('#fetch-orders').on('click', function() {
        fetchOrders(1, true);
    });

    // Add pagination handler
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && !isNaN(page) && !$(this).parent().hasClass('disabled')) {
            fetchOrders(parseInt(page));
        }
    });
});
