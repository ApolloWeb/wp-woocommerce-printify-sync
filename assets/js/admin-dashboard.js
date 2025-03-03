/**
 * Dashboard JavaScript - Chart Rendering and Dashboard Functionality
 * 
 * Version: 1.0.7
 * Date: 2025-03-03
 */
jQuery(function($) {
    console.log('Dashboard JS loaded on: 2025-03-03 11:45:42');
    
    // Wait for DOM to be fully loaded plus a short delay
    setTimeout(function() {
        console.log('Initializing charts now...');
        initializeCharts();
    }, 500);
    
    // Function to initialize all charts on the page
    function initializeCharts() {
        console.log('Chart initialization started');
        
        // Make sure Chart.js is loaded
        if (typeof Chart === 'undefined') {
            console.error('Chart.js not found - charts cannot be initialized');
            return;
        }
        
        // Sales Chart
        var salesChartEl = document.getElementById('sales-chart');
        if (salesChartEl) {
            try {
                console.log('Found sales chart element, initializing...');
                var ctx = salesChartEl.getContext('2d');
                var salesChart = new Chart(ctx, {
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
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0, 0, 0, 0.05)' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
                console.log('Sales chart initialized');
            } catch(e) {
                console.error('Error initializing sales chart:', e);
            }
        } else {
            console.log('Sales chart element not found');
        }
        
        // Categories Chart
        var categoriesChartEl = document.getElementById('product-categories-chart');
        if (categoriesChartEl) {
            try {
                console.log('Found categories chart element, initializing...');
                var ctx = categoriesChartEl.getContext('2d');
                var categoriesChart = new Chart(ctx, {
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
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { padding: 20 }
                            }
                        },
                        cutout: '70%'
                    }
                });
                console.log('Categories chart initialized');
            } catch(e) {
                console.error('Error initializing categories chart:', e);
            }
        } else {
            console.log('Categories chart element not found');
        }
        
        // API Chart
        var apiChartEl = document.getElementById('api-performance-chart');
        if (apiChartEl) {
            try {
                console.log('Found API chart element, initializing...');
                var ctx = apiChartEl.getContext('2d');
                var apiChart = new Chart(ctx, {
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
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0, 0, 0, 0.05)' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
                console.log('API chart initialized');
            } catch(e) {
                console.error('Error initializing API chart:', e);
            }
        } else {
            console.log('API chart element not found');
        }
        
        // Sync Progress
        var syncProgressEl = document.getElementById('sync-success-progress');
        if (syncProgressEl && typeof ProgressBar !== 'undefined') {
            try {
                console.log('Found sync progress element, initializing...');
                var syncProgress = new ProgressBar.Circle(syncProgressEl, {
                    color: '#7f54b3',
                    strokeWidth: 6,
                    trailWidth: 6,
                    trailColor: '#e2e8f0',
                    easing: 'easeInOut',
                    duration: 1400,
                    text: { autoStyleContainer: false },
                    from: { color: '#7f54b3', width: 6 },
                    to: { color: '#7f54b3', width: 6 },
                    step: function(state, circle) {
                        circle.path.setAttribute('stroke', state.color);
                        circle.path.setAttribute('stroke-width', state.width);
                    }
                });
                syncProgress.animate(0.982);
                console.log('Sync progress initialized');
            } catch(e) {
                console.error('Error initializing sync progress:', e);
            }
        } else {
            console.log('Sync progress element not found or ProgressBar not loaded');
        }
    }
});