jQuery(document).ready(function($) {
    $('#printify-sync-clear-products-btn').click(function(e) {
        e.preventDefault();

        if (confirm('Are you sure you want to clear all products?')) {
            $.ajax({
                url: printifySync.ajax_url,
                method: 'POST',
                data: {
                    action: 'printify_sync_clear_products',
                    security: printifySync.nonce,
                },
                success: function(response) {
                    if (response.success) {
                        alert('All products cleared');
                    }
                }
            });
        }
    });
});