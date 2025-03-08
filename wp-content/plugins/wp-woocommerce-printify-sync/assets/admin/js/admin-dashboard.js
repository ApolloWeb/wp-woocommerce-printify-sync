// Admin Dashboard Custom JS
jQuery(document).ready(function($) {
    // Initialize the sales chart with dummy data
    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
            datasets: [{
                label: 'Sales',
                data: [65, 59, 80, 81, 56, 55, 40],
                backgroundColor: 'rgba(60,141,188,0.2)',
                borderColor: 'rgba(60,141,188,1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Filter sales chart data by day/week/month/year
    $('#sales-chart-filter').on('change', function() {
        var filter = $(this).val();
        var newData;
        switch(filter) {
            case 'day':
                newData = [12, 19, 3, 5, 2, 3, 9];
                break;
            case 'week':
                newData = [50, 60, 70, 80, 90, 100, 110];
                break;
            case 'month':
                newData = [200, 250, 300, 350, 400, 450, 500];
                break;
            case 'year':
                newData = [1200, 1500, 1800, 2000, 2200, 2500, 2700];
                break;
        }
        salesChart.data.datasets[0].data = newData;
        salesChart.update();
    });
});