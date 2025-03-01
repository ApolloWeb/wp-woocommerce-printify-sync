(function($) {
    "use strict";

    $(document).ready(function() {
        console.log("WP WooCommerce Printify Sync Settings JS loaded.");

        $('#save-settings-btn').on('click', function(e) {
            e.preventDefault();
            var apiKey = $('#api_key').val();
            var shopId = $('#shop_id').val();
            var syncFrequency = $('#sync_frequency').val();

            wpWpsApiCall('wpwps_save_settings', {
                api_key: apiKey,
                shop_id: shopId,
                sync_frequency: syncFrequency
            }, function(data) {
                alert(data.message);
                $('#settings-response').html('<p>' + data.message + '</p>');
            }, function(error) {
                alert("Error: " + error);
            });
        });
    });
})(jQuery);