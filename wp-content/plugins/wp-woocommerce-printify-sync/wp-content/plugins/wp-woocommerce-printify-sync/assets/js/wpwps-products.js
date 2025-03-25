/* JavaScript for the products page */
jQuery(document).ready(function($) {
    $('#sync-products').on('click', function() {
        $.post(ajaxurl, { action: 'sync_products' }, function(response) {
            alert(response.data);
        });
    });
});
