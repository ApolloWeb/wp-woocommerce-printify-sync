// File: js/printify-sync-dashboard.js

document.addEventListener('DOMContentLoaded', function () {
    // Function to load dashboard data via AJAX.
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
                    // Update stats boxes.
                    document.getElementById('stat-synced-products').textContent = data.stats.active_products;
                    document.getElementById('stat-active-shops').textContent = data.stats.product_syncs;
                    document.getElementById('stat-recent-orders').textContent = data.stats.orders_processed;
                    document.getElementById('stat-last-sync').textContent = '2 hrs ago';

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

    // Initial chart instance.
    let salesChartInstance = null;

    // Render the sales chart using Chart.js.
    function renderSalesChart(chartData) {
        const ctx = document.getElementById('salesChart').getContext('2d');
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
                    backgroundColor: 'rgba(127, 84, 179, 0.1)',
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

    // Event listener for filter buttons.
    function setupFilterButtons() {
        const buttons = document.querySelectorAll('.filter-btn');
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                const period = button.getAttribute('data-period');
                console.log('Filtering sales by', period);
                // In production, update chart data based on selected period.
            });
        });
    }

    // Load dashboard data and setup filters.
    loadDashboardData();
    setupFilterButtons();
});