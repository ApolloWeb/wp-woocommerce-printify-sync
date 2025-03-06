/* Error Logs scripts go here */
jQuery(document).ready(function($) {
    $('#clear-logs').on('click', function() {
        $('#logs-status').html('<div class="alert alert-info"><?php _e("Clearing logs, please wait...", "wp-woocommerce-printify-sync"); ?></div>');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'clear_error_logs'
            },
            success: function(response) {
                $('#logs-status').html('<div class="alert alert-success"><?php _e("Logs cleared successfully!", "wp-woocommerce-printify-sync"); ?></div>');
                $('.logs-container').html(''); // Clear logs display
            },
            error: function() {
                $('#logs-status').html('<div class="alert alert-danger"><?php _e("An error occurred while clearing logs.", "wp-woocommerce-printify-sync"); ?></div>');
            }
        });
    });
});