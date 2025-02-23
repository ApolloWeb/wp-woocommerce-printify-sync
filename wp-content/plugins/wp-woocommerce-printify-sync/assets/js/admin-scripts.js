jQuery(document).ready(function($) {
    $('#test-printify-products').on('click', function(event) {
        event.preventDefault();

        var data = {
            action: 'test_printify_products',
            nonce: PrintifySync.nonce
        };

        $.post(PrintifySync.ajax_url, data, function(response) {
            if (response.success) {
                var products = response.data;
                var output = '<h3>Products:</h3><ul>';

                $.each(products.data, function(index, product) {
                    output += '<li>' + product.title + ' (' + product.print_provider.title + ')</li>';
                });

                output += '</ul>';
                $('#printify-products-results').html(output);
            } else {
                $('#printify-products-results').html('<p style="color: red;">' + response.data.message + '</p>');
            }
        });
    });
});