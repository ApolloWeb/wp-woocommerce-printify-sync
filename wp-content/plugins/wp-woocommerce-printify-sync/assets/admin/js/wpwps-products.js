(function($) {
    'use strict';

    const WPWPSProducts = {
        init: function() {
            this.initDataTable();
            this.initFilters();
            this.initBulkActions();
        },

        initDataTable: function() {
            const table = $('#products-table');
            if (!table.length) return;

            this.dataTable = table.DataTable({
                serverSide: true,
                ajax: {
                    url: wpwpsAdmin.ajaxUrl,
                    data: function(d) {
                        d.action = 'wpwps_get_products';
                        d._ajax_nonce = wpwpsAdmin.nonce;
                        d.filters = WPWPSProducts.getFilters();
                    }
                },
                columns: [
                    { data: 'checkbox', orderable: false },
                    { data: 'image', orderable: false },
                    { data: 'title' },
                    { data: 'sku' },
                    { data: 'status' },
                    { data: 'last_sync' },
                    { data: 'actions', orderable: false }
                ]
            });
        }
    };

    $(document).ready(() => WPWPSProducts.init());

})(jQuery);
