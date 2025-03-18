jQuery(document).ready(function($){
    $('#retrieve-products').on('click', function(e){
        e.preventDefault();
        $.ajax({
            url: wpwpsAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'retrieve_printify_products',
                _ajax_nonce: wpwpsAdmin.nonce
            },
            beforeSend: function(){
                $('#progress-bar').css('width', '0%');
            },
            success: function(response) {
                if(response.success) {
                    $('#products-list').html(response.data.html);
                    $('#import-products').show();
                    $('#progress-bar').css('width', '100%');
                } else {
                    alert(response.data.message || 'Error retrieving products.');
                }
            }
        });
    });

    $('#import-products').on('click', function(e){
        e.preventDefault();
        $.ajax({
            url: wpwpsAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'import_printify_products',
                _ajax_nonce: wpwpsAdmin.nonce
            },
            beforeSend: function(){
                $('#progress-bar').css('width', '0%');
            },
            success: function(response) {
                if(response.success) {
                    alert(response.data.message);
                    $('#progress-bar').css('width', '100%');
                } else {
                    alert(response.data.message || 'Error importing products.');
                }
            }
        });
    });
});
