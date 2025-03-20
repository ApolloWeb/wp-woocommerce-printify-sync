jQuery(document).ready(function($) {
    // ...existing code...

    function fetchProducts(refreshCache = false) {
        const button = $('#fetch-products');
        const originalHtml = button.html();
        showLoading(button);

        $('#products-table tbody').html('<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading products from Printify...</td></tr>');

        const url = new URL(wpwps_data.ajax_url);
        url.searchParams.append('action', 'printify_sync');
        url.searchParams.append('action_type', 'fetch_printify_products');
        url.searchParams.append('nonce', wpwps_data.nonce);
        url.searchParams.append('refresh_cache', refreshCache);

        $.ajax({
            url: url.toString(),
            type: 'GET',
            success: function(response) {
                // ...existing code...
            },
            error: function(xhr, status, error) {
                // ...existing code...
            },
            complete: function() {
                // ...existing code...
            }
        });
    }

    // ...existing code...
});
