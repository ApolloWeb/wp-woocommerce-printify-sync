(function($) {
    'use strict';

    const WPWPS_Dashboard = {
        charts: {},
        
        init: function() {
            this.initCharts();
            this.initSyncStatus();
            this.bindEvents();
        },

        initCharts: function() {
            this.charts.syncStats = new WPWPS.Charts.SyncStats('#sync-stats-chart');
            this.charts.productTypes = new WPWPS.Charts.ProductTypes('#product-types-chart');
        },

        initSyncStatus: function() {
            WPWPS.SyncStatus.init({
                selector: '#current-sync-status',
                updateInterval: 5000
            });
        },

        bindEvents: function() {
            $('#start-sync').on('click', this.handleStartSync.bind(this));
            $('#refresh-stats').on('click', this.handleRefreshStats.bind(this));
        },

        handleStartSync: function(e) {
            e.preventDefault();
            // Implementation
        },

        handleRefreshStats: function(e) {
            e.preventDefault();
            // Implementation
        }
    };

    $(document).ready(function() {
        WPWPS_Dashboard.init();
    });

})(jQuery);