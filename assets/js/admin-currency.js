jQuery(document).ready(function($) {
    $('#update-currencies-btn').on('click', function() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'update_currencies'
            },
            success: function(response) {
                if (response.success) {
                    alert('Currencies updated successfully.');
                    location.reload();
                } else {
                    alert('Failed to update currencies.');
                }
            },
            error: function() {
                alert('An error occurred while updating currencies.');
            }
        });
    });
});