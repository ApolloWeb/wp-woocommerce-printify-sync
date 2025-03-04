document.addEventListener('DOMContentLoaded', function () {
    /**
     * Load dashboard data via AJAX.
     */
    function loadDashboardData() {
        jQuery.ajax({
            url: PrintifySyncDashboard.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'printify_sync_dashboard_data',
                nonce: PrintifySyncDashboard.nonce
            },
            success: function (response) {
                if (response.success) {
                    const data = response.data;
                    // Update stats boxes with actual widget data.
                    document.getElementById('stat-active-shops').textContent = data.stats.active_shops;
                    document.getElementById('stat-synced-products').textContent = data.stats.synced_products;
                    document.getElementById('stat-recent-orders').textContent = data.stats.recent_orders;
                    document.getElementById('stat-last-sync').textContent = data.stats.last_sync;
                    // Render the sales chart.
                    renderSalesChart(data.charts.sales);
                } else {
                    console.error('Dashboard data load error:', response.data.message);
                }
            },
            error: function (error) {
                console.error('AJAX error:', error);
            }
        });
    }

    let salesChartInstance = null;

    /**
     * Render the sales chart using Chart.js.
     */
    function renderSalesChart(chartData) {
        const canvasElem = document.getElementById('salesChart');
        if (!canvasElem) {
            return;
        }
        const ctx = canvasElem.getContext('2d');
        if (salesChartInstance) {
            salesChartInstance.destroy();
        }
        salesChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Sales',
                    data: chartData.data,
                    borderColor: '#7f54b3',
                    backgroundColor: 'rgba(127,84,179,0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    /**
     * Setup filter buttons to update sales chart; implement your filtering logic here.
     */
    function setupFilterButtons() {
        const buttons = document.querySelectorAll('.filter-btn');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const period = btn.getAttribute('data-period');
                console.log('Filter sales chart by:', period);
                // TODO: Update chartData based on period via AJAX or dynamic lookup.
            });
        });
    }

    // Initialize dashboard.
    loadDashboardData();
    setupFilterButtons();
});