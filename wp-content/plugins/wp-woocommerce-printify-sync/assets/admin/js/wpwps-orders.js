(function($) {
    'use strict';

    const WPWPSOrders = {
        init: function() {
            this.initDataTable();
            this.initDateRangePicker();
            this.initStatusFilters();
        },

        initDataTable: function() {
            const table = $('#orders-table');
            if (!table.length) return;

            this.dataTable = table.DataTable({
                serverSide: true,
                ajax: {
                    url: wpwpsAdmin.ajaxUrl,
                    data: function(d) {
                        d.action = 'wpwps_get_orders';
                        d._ajax_nonce = wpwpsAdmin.nonce;
                        d.filters = WPWPSOrders.getFilters();
                    }
                },
                columns: [
                    { data: 'order_number' },
                    { data: 'customer' },
                    { data: 'status' },
                    { data: 'printify_status' },
                    { data: 'total' },
                    { data: 'date' },
                    { data: 'actions', orderable: false }
                ]
            });
        }
    };

    $(document).ready(() => WPWPSOrders.init());

})(jQuery);
