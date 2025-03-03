/**
 * Dashboard JavaScript - Chart Rendering and Dashboard Functionality
 * 
 * Version: 1.2.0
 * Date: 2025-03-03 12:28:57
 * Author: ApolloWeb
 */

// Store chart instances globally for reference
var printifySyncCharts = {};

jQuery(function($) {
    console.log('Dashboard JS loaded, timestamp:', new Date().toISOString());
    console.log('User:', printifySyncData ? printifySyncData.currentUser : 'ApolloWeb');
    
    // Wait until window is fully loaded for chart initialization
    $(window).on('load', function() {
        console.log('Window fully loaded, initializing charts');
        setTimeout(initializeCharts, 300);
    });
    
    function initializeCharts() {
        console.log('Starting chart initialization...');
        
        // Check if Chart.js exists
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
            return;
        }
        
        // 1. Sales Chart
        var salesCanvas = document.getElementById('sales-chart');
        if (salesCanvas) {
            console.log('Found sales chart element');
            
            try {
                // Clear any existing chart
                if (printifySyncCharts.salesChart) {
                    printifySyncCharts.salesChart.destroy();
                }
                
                var ctx = salesCanvas.getContext('2d');
                printifySyncCharts.salesChart = new Chart(ctx, {
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
                console.log('Sales chart initialized');
            } catch (e) {
                console.error('Error initializing sales chart:', e);
            }
        }
        
        // 2. Categories Chart
        var categoriesCanvas = document.getElementById('product-categories-chart');
        if (categoriesCanvas) {
            console.log('Found categories chart element');
            
            try {
                // Clear any existing chart
                if (printifySyncCharts.categoriesChart) {
                    printifySyncCharts.categoriesChart.destroy();
                }
                
                var ctx = categoriesCanvas.getContext('2d');
                printifySyncCharts.categoriesChart = new Chart(ctx, {
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
                                labels: {
                                    padding: 20
                                }
                            }
                        },
                        cutout: '70%'
                    }
                });
                console.log('Categories chart initialized');
            } catch (e) {
                console.error('Error initializing categories chart:', e);
            }
        }
        
        // 3. API Chart
        var apiCanvas = document.getElementById('api-performance-chart');
        if (apiCanvas) {
            console.log('Found API chart element');
            
            try {
                // Clear any existing chart
                if (printifySyncCharts.apiChart) {
                    printifySyncCharts.apiChart.destroy();
                }
                
                var ctx = apiCanvas.getContext('2d');
                printifySyncCharts.apiChart = new Chart(ctx, {
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
                console.log('API chart initialized');
            } catch (e) {
                console.error('Error initializing API chart:', e);
            }
        }
        
        // 4. Sync Progress
        var syncProgressEl = document.getElementById('sync-success-progress');
        if (syncProgressEl && typeof ProgressBar !== 'undefined') {
            console.log('Found sync progress element');
            
            try {
                // Clear any existing content
                syncProgressEl.innerHTML = '';
                
                var syncProgress = new ProgressBar.Circle(syncProgressEl, {
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
                
                syncProgress.animate(0.982);
                console.log('Sync progress initialized');
            } catch (e) {
                console.error('Error initializing sync progress:', e);
            }
        }
        
        console.log('All charts initialized');
    }
});