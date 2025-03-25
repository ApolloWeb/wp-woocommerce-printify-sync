/* JavaScript for the orders page */
jQuery(document).ready(function($) {
    $('#sync-orders').on('click', function() {
        $.post(ajaxurl, { action: 'sync_orders' }, function(response) {
            alert(response.data);
        });
    });
});
