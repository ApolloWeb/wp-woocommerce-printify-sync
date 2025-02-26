jQuery(document).ready(function($) {
    function fetchPrintifyProducts() {
        var data = {
            action: 'fetch_printify_products',
            nonce: PrintifySync.nonce
        };

        return $.post(PrintifySync.ajax_url, data);
    }

    if (!window.PrintifyAjax) {
        window.PrintifyAjax = {};
    }

    window.PrintifyAjax.fetchPrintifyProducts = fetchPrintifyProducts;
});