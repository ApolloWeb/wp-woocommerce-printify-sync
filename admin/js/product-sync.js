(function($) {
    "use strict";

    $(document).ready(function() {
        console.log("WP WooCommerce Printify Sync Product Sync JS loaded.");

        $('#sync-products-btn').on('click', function(e) {
            e.preventDefault();
            wpWpsApiCall('wpwps_sync_products', { sync_type: 'all' }, function(data) {
                alert(data.message);
                $('#sync-status').html('<p>' + data.message + '</p>');
            }, function(error) {
                alert("Error: " + error);
            });
        });
    });
})(jQuery);