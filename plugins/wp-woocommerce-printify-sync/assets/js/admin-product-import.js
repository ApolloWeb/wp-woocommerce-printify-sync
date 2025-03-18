jQuery(document).ready(function($) {
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
            }
        });
    });
});