/* Product Sync scripts go here */
jQuery(document).ready(function($) {
    $('#sync-products').on('click', function() {
        $('#sync-status').html('<div class="alert alert-info"><?php _e("Syncing products, please wait...", "wp-woocommerce-printify-sync"); ?></div>');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'sync_printify_products'
            },
            success: function(response) {
                $('#sync-status').html('<div class="alert alert-success"><?php _e("Products synced successfully!", "wp-woocommerce-printify-sync"); ?></div>');
            },
            error: function() {
                $('#sync-status').html('<div class="alert alert-danger"><?php _e("An error occurred during syncing.", "wp-woocommerce-printify-sync"); ?></div>');
            }
        });
    });
});