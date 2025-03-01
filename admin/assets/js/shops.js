jQuery(document).ready(function($) {
    function fetchShops() {
        $.ajax({
            url: printifySync.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_printify_shops',
                nonce: printifySync.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderShops(response.data);
                } else {
                    showError(response.data.message || 'Error fetching shops');
                }
            },
            error: function(xhr, status, error) {
                showError('AJAX error: ' + error);
            }
        });
    }

    function renderShops(shops) {
        var $tableBody = $('#shops-table-body');
        $tableBody.empty();

        if (!shops || shops.length === 0) {
            showError('No shops found for this account');
            return;
        }

        shops.forEach(function(shop) {
            var $row = $('<tr>');
            $row.append($('<td>').text(shop.title));
            $row.append($('<td>').text(shop.id));
            $row.append($('<td>').html('<button type="button" class="button select-shop-button" data-shop-id="' + shop.id + '" data-shop-name="' + shop.title + '">Select</button>'));
            $tableBody.append($row);
        });

        // Auto-select the first shop if no shop is currently selected
        if (!$('#wp_woocommerce_printify_sync_selected_shop').val() && $('.select-shop-button').length) {
            $('.select-shop-button').first().click();
        }

        $('.select-shop-button').on('click', function() {
            var shopId = $(this).data('shop-id');
            var shopName = $(this).data('shop-name');

            // Update the hidden input with the selected shop ID
            $('#wp_woocommerce_printify_sync_selected_shop').val(shopId);

            // Update the button styles
            $('.select-shop-button').removeClass('button-primary').addClass('button-secondary');
            $(this).removeClass('button-secondary').addClass('button-primary');

            // Optionally, display a message or perform other actions
            console.log('Selected Shop:', shopName, shopId);
        });
    }

    function showError(message) {
        $('#shops-message').html('<div class="alert alert-error">' + message + '</div>');
    }

    // Fetch shops on page load
    fetchShops();
});