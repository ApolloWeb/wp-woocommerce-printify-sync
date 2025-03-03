jQuery(document).ready(function($) {
    // Chart.js and ProgressBar.js initialization
    if (typeof Chart !== 'undefined') {
        // Incoming Tickets Chart
        var ctx = document.getElementById('incoming-tickets-chart').getContext('2d');
        var incomingTicketsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Refund Request', 'Product Inquiry', 'Order Inquiry', 'Other'],
                datasets: [{
                    label: 'Tickets',
                    data: [12, 19, 3, 5],
                    backgroundColor: [
                        'rgba(108, 99, 255, 0.2)',
                        'rgba(108, 99, 255, 0.2)',
                        'rgba(108, 99, 255, 0.2)',
                        'rgba(108, 99, 255, 0.2)'
                    ],
                    borderColor: [
                        'rgba(108, 99, 255, 1)',
                        'rgba(108, 99, 255, 1)',
                        'rgba(108, 99, 255, 1)',
                        'rgba(108, 99, 255, 1)'
                    ],
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

        // Order Tracking Chart
        var ctx = document.getElementById('order-tracking-chart').getContext('2d');
        var orderTrackingChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Orders',
                    data: [65, 59, 80, 81, 56, 55, 40],
                    fill: false,
                    borderColor: 'rgba(108, 99, 255, 1)',
                    tension: 0.1
                }]
            },
            options: {}
        });

        // Webhook Status Chart
        var ctx = document.getElementById('webhook-status-chart').getContext('2d');
        var webhookStatusChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Success', 'Failure'],
                datasets: [{
                    label: 'Webhooks',
                    data: [5, 2],
                    backgroundColor: [
                        'rgba(108, 99, 255, 0.2)',
                        'rgba(255, 99, 132, 0.2)'
                    ],
                    borderColor: [
                        'rgba(108, 99, 255, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {}
        });
    }

    if (typeof ProgressBar !== 'undefined') {
        // Product Sync Summary Circular Progress
        var productSyncProgress = new ProgressBar.Circle('#product-sync-progress', {
            color: '#aaa',
            // This has to be the same size as the maximum width to
            // prevent clipping
            strokeWidth: 4,
            trailWidth: 1,
            easing: 'easeInOut',
            duration: 1400,
            text: {
                autoStyleContainer: false
            },
            from: { color: '#aaa', width: 1 },
            to: { color: '#6C63FF', width: 4 },
            // Set default step function for all animate calls
            step: function(state, circle) {
                circle.path.setAttribute('stroke', state.color);
                circle.path.setAttribute('stroke-width', state.width);

                var value = Math.round(circle.value() * 100);
                if (value === 0) {
                    circle.setText('');
                } else {
                    circle.setText(value + '%');
                }

            }
        });
        productSyncProgress.text.style.fontFamily = '"Raleway", Helvetica, sans-serif';
        productSyncProgress.text.style.fontSize = '2rem';
        productSyncProgress.animate(0.75);  // Number from 0.0 to 1.0
    }
});