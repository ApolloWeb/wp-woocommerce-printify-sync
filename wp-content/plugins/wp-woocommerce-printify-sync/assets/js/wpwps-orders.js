jQuery(document).ready(function($) {
    let currentPage = 1;
    const perPage = 10;

    function fetchOrders(page = 1, refreshCache = false) {
        currentPage = page;
        const button = $('#fetch-orders');
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        $('#orders-table tbody').html('<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading orders from Printify...</td></tr>');
        
        // Build URL with query parameters
        const url = new URL(wpwps_data.ajax_url);
        url.searchParams.append('action', 'printify_sync');
        url.searchParams.append('action_type', 'fetch_printify_orders');
        url.searchParams.append('nonce', wpwps_data.nonce);
        url.searchParams.append('page', page);
        url.searchParams.append('per_page', perPage);
        url.searchParams.append('refresh_cache', refreshCache);

        $.ajax({
            url: url.toString(),
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    // Handle success response
                    updateOrdersTable(response.data);
                } else {
                    // Handle error response
                    handleError(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                handleError('Failed to fetch orders: ' + error);
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
        // ... rest of the table update code ...
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
