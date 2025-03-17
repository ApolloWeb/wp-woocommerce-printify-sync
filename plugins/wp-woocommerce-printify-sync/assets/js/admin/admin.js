jQuery(document).ready(function ($) {
    function PrintifyAdmin() {
        this.initialize();
    }

    PrintifyAdmin.prototype.initialize = function () {
        this.initializeStats();
    };

    PrintifyAdmin.prototype.initializeStats = function () {
        // Ensure jQuery is defined before using it
        if (typeof $ === 'undefined' || typeof $.ajax === 'undefined') {
            console.error('jQuery or $.ajax is not defined');
            return;
        }

        this.updateStats();
    };

    PrintifyAdmin.prototype.updateStats = function () {
        $.ajax({
            url: wpwpsAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'wpwps_update_stats',
                nonce: wpwpsAdmin.nonce
            },
            success: function (response) {
                // Update stats on the dashboard
                $('#pending-orders .stat-value').text(response.data.pending_orders);
                $('#in-production .stat-value').text(response.data.in_production);
                $('#completed-orders .stat-value').text(response.data.completed_orders);
                $('#failed-orders .stat-value').text(response.data.failed_orders);
            },
            error: function (error) {
                console.error('Error updating stats:', error);
            }
        });
    };

    $(function () {
        new PrintifyAdmin();
    });
});