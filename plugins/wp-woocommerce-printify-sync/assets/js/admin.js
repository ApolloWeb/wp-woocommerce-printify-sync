jQuery(document).ready(function($) {
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
            }
        });
    });
});