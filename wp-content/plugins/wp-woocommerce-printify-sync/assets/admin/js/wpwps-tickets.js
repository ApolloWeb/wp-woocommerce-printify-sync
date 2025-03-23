(function($) {
    'use strict';

    const WPWPSTickets = {
        init: function() {
            this.initInboxView();
            this.initTicketActions();
            this.initAutoRefresh();
        },

        initInboxView: function() {
            const inbox = $('#tickets-inbox');
            if (!inbox.length) return;

            this.loadTickets();
            this.initFilters();
            this.initSearch();
        },

        loadTickets: function(page = 1) {
            WPWPS.api.get('get_tickets', {
                page: page,
                filters: this.getFilters()
            }).then(response => {
                if (response.success) {
                    this.renderTickets(response.data);
                }
            });
        }
    };

    $(document).ready(() => WPWPSTickets.init());

})(jQuery);
