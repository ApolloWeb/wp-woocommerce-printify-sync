jQuery(document).ready(function($){
    // Handler for settings form submission.
    $('#settings-form').on('submit', function(e){
        e.preventDefault();
        // ...existing AJAX code to save settings...
        console.log("Saving settings...");
        $.ajax({
            url: wpwpsAdmin.ajaxUrl,
            method: 'POST',
            data: $(this).serialize() + '&_ajax_nonce=' + wpwpsAdmin.nonce,
            success: function(response) {
                if(response.success){
                    alert(response.data.message);
                } else {
                    alert("Error saving settings.");
                }
            }
        });
    });
    // ...existing code...
});
