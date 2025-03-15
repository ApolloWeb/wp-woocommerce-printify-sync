(function($) {
    'use strict';

    const ProductImporter = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('#printify-import-products').on('click', this.importProducts);
        },

        importProducts: function(e) {
            e.preventDefault();
            const $button = $(this);
            const $status = $('#import-status');

            $button.prop('disabled', true);
            $status.html('Importing products...');

            $.ajax({
                url: printifyAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'import_printify_products',
                    _ajax_nonce: printifyAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.html(response.data.message);
                    } else {
                        $status.html('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    $status.html('Error: Failed to import products');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        }
    };

    $(document).ready(function() {
        ProductImporter.init();
    });
})(jQuery);