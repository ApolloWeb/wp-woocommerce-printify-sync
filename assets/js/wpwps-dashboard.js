/**
 * Dashboard scripts
 */
(function($) {
    'use strict';
    
    // Initialize charts when DOM is ready
    $(document).ready(function() {
        initSyncChart();
        initCategoryChart();
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
    
    /**
     * Initialize sync activity chart
     */
    function initSyncChart() {
        var ctx = document.getElementById('syncChart');
        if (!ctx) return;
        
        var colors = wpwps_admin.colors;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Products Synced',
                    data: [65, 59, 80, 81, 56, 55, 40],
                    fill: true,
                    backgroundColor: hexToRgba(colors.primary, 0.1),
                    borderColor: colors.primary,
                    tension: 0.4,
                    pointBackgroundColor: colors.primary
                }, {
                    label: 'Orders Processed',
                    data: [28, 48, 40, 19, 86, 27, 90],
                    fill: true,
                    backgroundColor: hexToRgba(colors.secondary, 0.1),
                    borderColor: colors.secondary,
                    tension: 0.4,
                    pointBackgroundColor: colors.secondary
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                interaction: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
    
    /**
     * Initialize category chart
     */
    function initCategoryChart() {
        var ctx = document.getElementById('categoryChart');
        if (!ctx) return;
        
        var colors = [
            wpwps_admin.colors.primary,
            wpwps_admin.colors.secondary,
            wpwps_admin.colors.tertiary,
            hexToRgba(wpwps_admin.colors.primary, 0.7),
            hexToRgba(wpwps_admin.colors.secondary, 0.7),
        ];
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['T-Shirts', 'Hoodies', 'Mugs', 'Posters', 'Other'],
                datasets: [{
                    data: [35, 25, 20, 15, 5],
                    backgroundColor: colors,
                    borderColor: wpwps_admin.colors.light,
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                },
                cutout: '60%',
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            }
        });
    }
    
    /**
     * Convert hex color to rgba
     */
    function hexToRgba(hex, alpha) {
        var r = parseInt(hex.slice(1, 3), 16),
            g = parseInt(hex.slice(3, 5), 16),
            b = parseInt(hex.slice(5, 7), 16);
        
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    }
    
})(jQuery);
