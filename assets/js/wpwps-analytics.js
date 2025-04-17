/**
 * Analytics page scripts
 */
(function($) {
    'use strict';
    
    // Initialize charts when DOM is ready
    $(document).ready(function() {
        initSalesChart();
        initProductChart();
        initDemographicsChart();
        initGeoChart();
    });
    
    /**
     * Initialize sales overview chart
     */
    function initSalesChart() {
        var ctx = document.getElementById('salesChart');
        if (!ctx) return;
        
        var colors = wpwps_admin.colors;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                datasets: [{
                    label: 'Sales',
                    data: [650, 590, 800, 810, 560, 550, 900],
                    fill: true,
                    backgroundColor: hexToRgba(colors.primary, 0.1),
                    borderColor: colors.primary,
                    tension: 0.4,
                    pointBackgroundColor: colors.primary
                }, {
                    label: 'Orders',
                    data: [280, 480, 400, 190, 860, 270, 800],
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
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('en-US', { 
                                        style: 'currency', 
                                        currency: 'USD' 
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
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
     * Initialize top products chart
     */
    function initProductChart() {
        var ctx = document.getElementById('productChart');
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
                    data: [350, 250, 200, 150, 50],
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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${context.label}: ${value} units (${percentage}%)`;
                            }
                        }
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
     * Initialize demographics chart
     */
    function initDemographicsChart() {
        var ctx = document.getElementById('demographicsChart');
        if (!ctx) return;
        
        var colors = wpwps_admin.colors;
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['18-24', '25-34', '35-44', '45-54', '55+'],
                datasets: [{
                    data: [15, 30, 25, 18, 12],
                    backgroundColor: [
                        hexToRgba(colors.tertiary, 0.8),
                        colors.primary,
                        colors.secondary,
                        hexToRgba(colors.primary, 0.6),
                        hexToRgba(colors.secondary, 0.6)
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${context.label}: ${percentage}%`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Initialize geographic distribution chart
     */
    function initGeoChart() {
        var ctx = document.getElementById('geoChart');
        if (!ctx) return;
        
        var colors = wpwps_admin.colors;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['USA', 'Canada', 'UK', 'Australia', 'Germany', 'France', 'Other'],
                datasets: [{
                    label: 'Sales by Country',
                    data: [1250, 750, 680, 420, 380, 320, 585],
                    backgroundColor: [
                        colors.primary,
                        hexToRgba(colors.primary, 0.8),
                        hexToRgba(colors.primary, 0.6),
                        hexToRgba(colors.primary, 0.4),
                        hexToRgba(colors.secondary, 0.8),
                        hexToRgba(colors.secondary, 0.6),
                        hexToRgba(colors.secondary, 0.4)
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw;
                                let total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                let percentage = Math.round((value / total) * 100);
                                
                                if ($('#showPercentages').is(':checked')) {
                                    return `${percentage}% (${value})`;
                                } else {
                                    return `${value} sales`;
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            drawBorder: false,
                            drawOnChartArea: true,
                            drawTicks: false,
                        }
                    },
                    y: {
                        grid: {
                            display: false,
                            drawBorder: false,
                            drawTicks: false
                        }
                    }
                }
            }
        });
        
        // Toggle percentage display
        $('#showPercentages').on('change', function() {
            initGeoChart(); // Redraw chart
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
