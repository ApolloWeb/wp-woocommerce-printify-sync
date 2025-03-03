/**
 * Dashboard Charts Initialization
 * Version: 1.1.1
 * Date: 2025-03-03 12:01:52
 * User: ApolloWeb
 */

// Use jQuery's ready function to ensure DOM is loaded
jQuery(function($) {
    console.log('CHARTS: Charts script loaded, preparing to initialize');
    
    // Delay chart initialization to ensure elements are ready
    setTimeout(initializeCharts, 300);
    
    function initializeCharts() {
        console.log('CHARTS: Initializing charts');
        
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            console.error('CHARTS ERROR: Chart.js not available!');
            loadChartJsDirectly(function() {
                console.log('CHARTS: Chart.js loaded directly, retrying...');
                initializeCharts();
            });
            return;
        }
        
        console.log('CHARTS: Chart.js detected, version ' + Chart.version);
        
        // 1. Sales Chart
        initSalesChart();
        
        // 2. Product Categories Chart
        initCategoriesChart();
        
        // 3. API Performance Chart
        initApiPerformanceChart();
        
        // 4. Sync Success Progress
        initSyncProgress();
    }
    
    // Sales Chart
    function initSalesChart() {
        var element = document.getElementById('sales-chart');
        
        if (!element) {
            console.log('CHARTS: Sales chart element not found');
            return;
        }
        
        try {
            console.log('CHARTS: Creating sales chart');
            var ctx = element.getContext('2d');
            
            new Chart(ctx, {
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
            console.log('CHARTS: Sales chart created successfully');
        } catch (e) {
            console.error('CHARTS ERROR: Failed creating sales chart', e);
        }
    }
    
    // Product Categories Chart
    function initCategoriesChart() {
        var element = document.getElementById('product-categories-chart');
        
        if (!element) {
            console.log('CHARTS: Categories chart element not found');
            return;
        }
        
        try {
            console.log('CHARTS: Creating categories chart');
            var ctx = element.getContext('2d');
            
            new Chart(ctx, {
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
            console.log('CHARTS: Categories chart created successfully');
        } catch (e) {
            console.error('CHARTS ERROR: Failed creating categories chart', e);
        }
    }
    
    // API Performance Chart
    function initApiPerformanceChart() {
        var element = document.getElementById('api-performance-chart');
        
        if (!element) {
            console.log('CHARTS: API performance chart element not found');
            return;
        }
        
        try {
            console.log('CHARTS: Creating API performance chart');
            var ctx = element.getContext('2d');
            
            new Chart(ctx, {
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
            console.log('CHARTS: API chart created successfully');
        } catch (e) {
            console.error('CHARTS ERROR: Failed creating API performance chart', e);
        }
    }
    
    // Sync Success Progress
    function initSyncProgress() {
        var element = document.getElementById('sync-success-progress');
        
        if (!element) {
            console.log('CHARTS: Sync progress element not found');
            return;
        }
        
        if (typeof ProgressBar === 'undefined') {
            console.error('CHARTS ERROR: ProgressBar.js not available!');
            loadProgressBarDirectly(function() {
                console.log('CHARTS: ProgressBar.js loaded directly, retrying...');
                initSyncProgress();
            });
            return;
        }
        
        try {
            console.log('CHARTS: Creating sync progress');
            var circle = new ProgressBar.Circle(element, {
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
            
            circle.animate(0.982); // 98.2% success rate
            console.log('CHARTS: Sync progress created successfully');
        } catch (e) {
            console.error('CHARTS ERROR: Failed creating sync progress', e);
        }
    }
    
    // Helper to load Chart.js directly if it's missing
    function loadChartJsDirectly(callback) {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js';
        script.onload = callback;
        document.head.appendChild(script);
    }
    
    // Helper to load ProgressBar.js directly if it's missing
    function loadProgressBarDirectly(callback) {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/progressbar.js@1.1.0/dist/progressbar.min.js';
        script.onload = callback;
        document.head.appendChild(script);
    }
});