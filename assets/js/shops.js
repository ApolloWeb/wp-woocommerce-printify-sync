jQuery(document).ready(function($) {
    // Fetch shops on page load.
    $.post(wwpsAjax.ajaxurl, { action: 'wwps_get_shops' }, function(response) {
        var shopsTable = $('.wwps-shops tbody');
        shopsTable.empty();

        if (response.success) {
            var shops = response.data;

            if (shops.length > 0) {
                shops.forEach(function(shop) {
                    var row = '<tr>' +
                        '<td>' + shop.title + '</td>' +
                        '<td>' + shop.id + '</td>' +
                        '<td><button class="button select-shop" data-shop-id="' + shop.id + '">Select</button></td>' +
                        '</tr>';
                    shopsTable.append(row);
                });
            } else {
                shopsTable.append('<tr><td colspan="3">No shops found.</td></tr>');
            }
        } else {
            console.error(response.data.debug);
            shopsTable.append('<tr><td colspan="3">Failed to load shops. Error: ' + response.data.message + '</td></tr>');
        }
    });

    // Handle shop selection.
    $('.wwps-shops').on('click', '.select-shop', function() {
        var shopId = $(this).data('shop-id');
        console.log('Selected shop ID:', shopId);
        // Implement the action to store the selected shop ID.
    });

    // Handle manual import.
    $('#manual-import').on('click', function() {
        var shopId = $('.wwps-shops .select-shop').data('shop-id');
        if (!shopId) {
            alert('Please select a shop first.');
            return;
        }

        $('#import-progress').show();
        $('#progress-bar-fill').css('width', '0%');

        $.post(wwpsAjax.ajaxurl, { action: 'wwps_import_products', shop_id: shopId }, function(response) {
            if (response.success) {
                alert('Products imported successfully.');
            } else {
                alert('Failed to import products.');
            }
        });
    });
});