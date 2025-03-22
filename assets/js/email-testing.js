jQuery(document).ready(function($) {
    $('#send-test').on('click', function() {
        const button = $(this);
        const email = $('#test-email').val();
        
        if (!email) {
            showError('Please enter a test email address');
            return;
        }

        button.prop('disabled', true);
        
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_email',
                nonce: wpwps_data.nonce,
                test_email: email
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                } else {
                    showError(response.data.message);
                }
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    function showSuccess(message) {
        $('#test-result')
            .removeClass('hidden error')
            .addClass('success')
            .html(`<i class="fas fa-check-circle"></i> ${message}`);
    }

    function showError(message) {
        $('#test-result')
            .removeClass('hidden success')
            .addClass('error')
            .html(`<i class="fas fa-exclamation-circle"></i> ${message}`);
    }
});
