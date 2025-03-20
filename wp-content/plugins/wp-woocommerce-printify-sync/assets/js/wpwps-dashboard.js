jQuery(document).ready(function($) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    let salesChart = null;

    const chartData = {
        day: {
            labels: ['12am', '4am', '8am', '12pm', '4pm', '8pm'],
            data: [5, 10, 15, 25, 20, 30]
        },
        week: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            data: [150, 120, 140, 180, 200, 220, 190]
        },
        month: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            data: [800, 950, 1100, 1200]
        },
        year: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            data: [9500, 10200, 11800, 12500, 11900, 13100, 12800, 13500, 14200, 14800, 15100, 16200]
        }
    };

    function createChart(period) {
        if (salesChart) {
            salesChart.destroy();
        }

        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData[period].labels,
                datasets: [{
                    label: 'Sales',
                    data: chartData[period].data,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // This is crucial for responsive chart
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Initialize with monthly data
    createChart('month');

    // Handle period switches
    $('.btn-group .btn').on('click', function() {
        $('.btn-group .btn').removeClass('active');
        $(this).addClass('active');
        createChart($(this).data('period'));
    });
});
