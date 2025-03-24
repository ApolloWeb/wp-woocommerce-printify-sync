/**
 * Dashboard specific JavaScript
 */

(function($) {
    'use strict';

    // Page state
    let dashboardRefreshInterval = null;

    // Initialize dashboard functionality
    function initDashboard() {
        setupCharts();
        setupSyncButtons();
        setupActivityPolling();
        setupSearch();
        setupAPIHealthCheck();
    }

    // Setup dashboard charts
    function setupCharts() {
        if (typeof Chart === 'undefined') return;

        const ctx = document.getElementById('syncActivityChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: wpwps.chart_labels || [],
                datasets: [{
                    label: wpwps.i18n.products_synced,
                    data: wpwps.product_stats || [],
                    borderColor: 'rgb(99, 102, 241)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // Setup sync action buttons
    function setupSyncButtons() {
        $('#wpwps-sync-all').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).addClass('wpwps-loading');
            
            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_sync_all',
                    nonce: wpwps.nonce
                },
                success: function(response) {
                    if (response.success) {
                        wpwpsToast.success(response.data.message);
                        refreshStats();
                    } else {
                        wpwpsToast.error(response.data.message);
                    }
                },
                error: function() {
                    wpwpsToast.error(wpwps.i18n.ajax_error);
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wpwps-loading');
                }
            });
        });
    }

    // Setup periodic activity polling
    function setupActivityPolling() {
        dashboardRefreshInterval = setInterval(refreshStats, 30000); // 30 seconds
    }

    // Refresh dashboard statistics
    function refreshStats() {
        $.ajax({
            url: wpwps.ajax_url,
            type: 'GET',
            data: {
                action: 'wpwps_get_dashboard_stats',
                nonce: wpwps.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardStats(response.data);
                }
            }
        });
    }

    // Update dashboard statistics UI
    function updateDashboardStats(data) {
        // Update stat cards
        $('.wpwps-stat-value').each(function() {
            const $stat = $(this);
            const key = $stat.data('stat');
            if (data[key]) {
                $stat.text(data[key]);
            }
        });

        // Update API health indicators
        updateAPIHealthStatus(data.api_health);
    }

    // Update API health status indicators
    function updateAPIHealthStatus(status) {
        const $indicator = $('.wpwps-api-health');
        $indicator.removeClass('wpwps-status-healthy wpwps-status-warning wpwps-status-error')
                 .addClass(`wpwps-status-${status.status}`);
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.wpwps-dashboard').length) {
            initDashboard();
        }
    });

    // Cleanup on page unload
    $(window).on('unload', function() {
        if (dashboardRefreshInterval) {
            clearInterval(dashboardRefreshInterval);
        }
    });

})(jQuery);
