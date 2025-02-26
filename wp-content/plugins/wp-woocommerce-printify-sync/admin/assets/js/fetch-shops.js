jQuery(document).ready(function($) {
    function fetchPrintifyShops() {
        var data = {
            action: 'fetch_printify_shops',
            nonce: PrintifySync.nonce
        };

        return $.post(PrintifySync.ajax_url, data);
    }

    if (!window.PrintifyAjax) {
        window.PrintifyAjax = {};
    }

    window.PrintifyAjax.fetchPrintifyShops = fetchPrintifyShops;
});