jQuery(document).ready(function($) {
    console.log('WP WooCommerce Printify Sync settings loaded');

    $('#test-connection').on('click', function(e) {
        e.preventDefault();
        wpwpps.notify('Testing connection...', 'info');
        $.post(wpwpps_ajax.ajax_url, {
            action: 'wpwpps_test_connection',
            printify_api_key: $('#printify_api_key').val(),
            api_endpoint: $('#api_endpoint').val()
        }, function(response) {
            if(response.success) {
                wpwpps.notify(response.data.message, 'success');
                let shopSelect = $('#shop_id');
                shopSelect.empty();
                $.each(response.data.shops, function(i, shop) {
                    shopSelect.append($('<option>', {value: shop.id, text: shop.name}));
                });
            } else {
                wpwpps.notify(response.data.message, 'error');
            }
        });
    });

    // Handle settings form submission
    $('#wpwpps-settings-form').on('submit', function(e) {
        e.preventDefault();
        // ...existing form submission code...
    });

    // Handle monthly estimate calculation
    $('#test-monthly-estimate').on('click', function(e) {
        e.preventDefault();
        // ...existing estimate calculation code...
    });
});
