jQuery(document).ready(function($) {
    console.log('WP WooCommerce Printify Sync dashboard loaded');

    // Initialize Charts
    if ($('#salesChart').length) {
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['January', 'February', 'March', 'April'],
                datasets: [{
                    label: 'Sales',
                    data: [150, 200, 180, 220],
                    backgroundColor: 'rgba(150, 88, 138, 0.2)',
                    borderColor: 'rgba(150, 88, 138, 1)',
                    fill: true
                }]
            },
            options: { responsive: true }
        });
    }

    // Dashboard widget loading simulation
    setTimeout(() => $('#queued-emails').html('5 emails queued'), 1000);
    setTimeout(() => $('#import-queue-progress').html('<progress value="30" max="100"></progress> 30%'), 1200);
    setTimeout(() => $('#sync-results').html('Last sync: Success (3 products)'), 1400);
});
