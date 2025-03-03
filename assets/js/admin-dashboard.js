document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    const salesChartElement = document.getElementById('sales-chart');
    if (salesChartElement) {
        const salesChartCtx = salesChartElement.getContext('2d');
        const salesChart = new Chart(salesChartCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales',
                    data: [4500, 5200, 4800, 5800, 6000, 5500],
                    backgroundColor: 'rgba(127, 84, 179, 0.1)',
                    borderColor: 'rgba(127, 84, 179, 1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Product Categories Chart
    const categoriesChartElement = document.getElementById('product-categories-chart');
    if (categoriesChartElement) {
        const categoriesChartCtx = categoriesChartElement.getContext('2d');
        const categoriesChart = new Chart(categoriesChartCtx, {
            type: 'doughnut',
            data: {
                labels: ['T-Shirts', 'Mugs', 'Posters', 'Phone Cases', 'Other'],
                datasets: [{
                    data: [45, 20, 15, 12, 8],
                    backgroundColor: [
                        'rgba(127, 84, 179, 0.8)',
                        'rgba(163, 101, 151, 0.8)',
                        'rgba(66, 153, 225, 0.8)',
                        'rgba(72, 187, 120, 0.8)',
                        'rgba(237, 137, 54, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%'
            }
        });
    }
    
    // API Performance Chart
    const apiChartElement = document.getElementById('api-performance-chart');
    if (apiChartElement) {
        const apiChartCtx = apiChartElement.getContext('2d');
        const apiChart = new Chart(apiChartCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Response Time (ms)',
                    data: [320, 420, 380, 290, 310, 250, 270],
                    backgroundColor: 'rgba(127, 84, 179, 0.7)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Sync Success Progress Circle
    const syncProgressElement = document.getElementById('sync-success-progress');
    if (syncProgressElement && typeof ProgressBar !== 'undefined') {
        const syncProgress = new ProgressBar.Circle(syncProgressElement, {
            color: '#7f54b3',
            strokeWidth: 6,
            trailWidth: 6,
            trailColor: '#e2e8f0',
            easing: 'easeInOut',
            duration: 1400,
            text: {
                autoStyleContainer: false
            },
            from: { color: '#7f54b3', width: 6 },
            to: { color: '#7f54b3', width: 6 },
            step: function(state, circle) {
                circle.path.setAttribute('stroke', state.color);
                circle.path.setAttribute('stroke-width', state.width);
            }
        });
        
        syncProgress.animate(0.982); // 98.2% success rate
    }
});