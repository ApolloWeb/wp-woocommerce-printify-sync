/**
 * JavaScript file: admin.js for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 * Time: 02:14:58
 */
jQuery(document).ready(function($) {
    $('#get-printify-products').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            url: printifyAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_printify_products',
                nonce: printifyAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var output = '<ul>';
                    $.each(response.data, function(index, product) {
                        output += '<li>' + product.name + '</li>';
                    });
                    output += '</ul>';
                    $('#printify-products-container').html(output);
                } else {
                    $('#printify-products-container').html('<p>Error fetching products.</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#printify-products-container').html('<p>AJAX Error: ' + error + '</p>');
            }
        });
    });
});
