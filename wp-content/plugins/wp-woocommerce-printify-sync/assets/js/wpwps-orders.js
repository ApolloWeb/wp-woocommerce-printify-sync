jQuery(document).ready(function($) {
    // ...existing code...

    function fetchOrders(page = 1, refreshCache = false) {
        currentPage = page;
        const button = $('#fetch-orders');
        const originalHtml = button.html();
        showLoading(button);

        $('#orders-table tbody').html('<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading orders from Printify...</td></tr>');
        
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
                // ...existing code...
            },
            error: function() {
                // ...existing code...
            },
            complete: function() {
                // ...existing code...
            }
        });
    }

    // ...existing code...
});
