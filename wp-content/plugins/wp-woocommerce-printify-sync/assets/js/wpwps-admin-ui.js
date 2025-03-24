(function($) {
    'use strict';

    // Initialize navbar interactivity
    function initNavbar() {
        const $navbar = $('.wpwps-navbar');
        let lastScroll = 0;

        $(window).on('scroll', () => {
            const currentScroll = window.pageYOffset;
            if (currentScroll > lastScroll) {
                $navbar.addClass('wpwps-navbar-hidden');
            } else {
                $navbar.removeClass('wpwps-navbar-hidden');
            }
            lastScroll = currentScroll;
        });
    }

    // Initialize charts with custom styling
    function initCharts() {
        if (typeof Chart === 'undefined') return;

        // Custom Chart.js defaults
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#64748b';
        
        // Initialize sync activity chart
        const ctx = document.getElementById('syncChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                // ... chart data
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                }
            }
        });
    }

    // Document ready
    $(document).ready(function() {
        initNavbar();
        initCharts();
    });

})(jQuery);
