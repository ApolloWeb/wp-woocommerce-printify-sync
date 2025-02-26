jQuery(document).ready(function($) {
    function displayShops(shops) {
        var output = '<h3>Shops:</h3><ul>';

        $.each(shops, function(index, shop) {
            if (shop && shop.title) {
                output += '<li>' + shop.title + '</li>';
            } else {
                output += '<li>Invalid shop data</li>';
            }
        });

        output += '</ul>';
        $('#printify-shops-results').html(output);
    }

    function displayProducts(products) {
        var output = '<h3>Products:</h3><ul>';

        $.each(products.data, function(index, product) {
            if (product && product.title && product.print_provider && product.print_provider.title) {
                output += '<li>' + product.title + ' (' + product.print_provider.title + ')</li>';
            } else {
                output += '<li>Invalid product data</li>';
            }
        });

        output += '</ul>';
        $('#printify-products-results').html(output);
    }

    function displayError(message) {
        $('#printify-results').html('<p style="color: red;">' + message + '</p>');
    }

    if (!window.PrintifyDOM) {
        window.PrintifyDOM = {};
    }

    window.PrintifyDOM.displayShops = displayShops;
    window.PrintifyDOM.displayProducts = displayProducts;
    window.PrintifyDOM.displayError = displayError;
});