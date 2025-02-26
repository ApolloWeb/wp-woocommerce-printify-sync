jQuery(document).ready(function($) {
    $('#fetch-printify-shops').on('click', function(event) {
        event.preventDefault();

        PrintifyAjax.fetchPrintifyShops().done(function(response) {
            if (response.success) {
                PrintifyDOM.displayShops(response.data);
            } else {
                PrintifyDOM.displayError(response.data.message);
            }
        });
    });

    $('#fetch-printify-products').on('click', function(event) {
        event.preventDefault();

        PrintifyAjax.fetchPrintifyProducts().done(function(response) {
            if (response.success) {
                PrintifyDOM.displayProducts(response.data);
            } else {
                PrintifyDOM.displayError(response.data.message);
            }
        });
    });
});