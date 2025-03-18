jQuery(document).ready(function($) {
    // Enable fetch shops button if API key is entered
    $('#printify_api_key').on('input', function() {
        var apiKey = $(this).val().trim();
        if (apiKey !== '') {
            $('#fetch-shops-button').prop('disabled', false);
        } else {
            $('#fetch-shops-button').prop('disabled', true);
        }
    });

    $('#fetch-shops-button').on('click', function(event) {
        event.preventDefault();

        $.ajax({
            url: printifySync.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_printify_shops',
                nonce: printifySync.nonce
            },
            success: function(response) {
                if (response.success) {
                    var shops = response.data;
                    var shopSelect = $('#printify_shop');
                    shopSelect.prop('disabled', false);
                    shopSelect.empty();
                    shopSelect.append('<option value="">-- Select Shop --</option>');
                    $.each(shops, function(index, shop) {
                        shopSelect.append('<option value="' + shop.id + '">' + shop.title + '</option>');
                    });
                } else {
                    alert('Failed to fetch shops.');
                }
            },
            error: function() {
                alert('An error occurred while fetching shops.');
            }
        });
    });

    $('#test-api-button').on('click', function(event) {
        event.preventDefault();

        $.ajax({
            url: printifySync.ajax_url,
            type: 'POST',
            data: {
                action: 'test_printify_api',
                nonce: printifySync.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#test-api-result').html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                } else {
                    $('#test-api-result').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function() {
                $('#test-api-result').html('<div class="notice notice-error"><p>An error occurred while testing the API.</p></div>');
            }
        });
    });

    $('#retrieve-products-button').on('click', function(event) {
        event.preventDefault();

        $.ajax({
            url: printifySync.ajax_url,
            type: 'POST',
            data: {
                action: 'retrieve_printify_products',
                nonce: printifySync.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Products retrieved successfully!');
                    // Display products in a table or list
                } else {
                    alert('Failed to retrieve products.');
                }
            },
            error: function() {
                alert('An error occurred while retrieving products.');
            }
        });
    });

    $('#import-products-button').on('click', function(event) {
        event.preventDefault();

        $.ajax({
            url: printifySync.ajax_url,
            type: 'POST',
            data: {
                action: 'import_printify_products',
                nonce: printifySync.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Product import started successfully!');
                    // Start progress indicator
                } else {
                    alert('Failed to start product import.');
                }
            },
            error: function() {
                alert('An error occurred while starting product import.');
            }
        });
    });
});