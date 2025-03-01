(function($) {
    "use strict";

    $(document).ready(function() {
        console.log("WP WooCommerce Printify Sync API Handler JS loaded.");

        // Example: Function to handle API calls
        function apiCall(action, data, successCallback, errorCallback) {
            $.post(wpwps_ajax.ajax_url, {
                action: action,
                nonce: wpwps_ajax.nonce,
                ...data
            })
            .done(function(response) {
                if (response.success) {
                    if (typeof successCallback === 'function') {
                        successCallback(response.data);
                    }
                } else {
                    if (typeof errorCallback === 'function') {
                        errorCallback(response.data);
                    }
                }
            });
        }

        // Export the function to be accessible globally
        window.wpWpsApiCall = apiCall;
    });
})(jQuery);