/* Order Sync scripts go here */
jQuery(document).ready(function($) {
    $('#sync-orders').on('click', function() {
        $('#sync-status').html('<div class="alert alert-info"><?php _e("Syncing orders, please wait...", "wp-woocommerce-printify-sync"); ?></div>');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'sync_printify_orders'
            },
            success: function(response) {
                $('#sync-status').html('<div class="alert alert-success"><?php _e("Orders synced successfully!", "wp-woocommerce-printify-sync"); ?></div>');
            },
            error: function() {
                $('#sync-status').html('<div class="alert alert-danger"><?php _e("An error occurred during syncing.", "wp-woocommerce-printify-sync"); ?></div>');
            }
        });
    });
});