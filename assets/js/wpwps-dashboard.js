jQuery(document).ready(function($) {
    // Show a welcome toast when dashboard loads
    showToast('Welcome to Printify Sync', 'Your dashboard is ready', 'info');
    
    // Initialize the orders chart
    initOrdersChart();
    
    // Function to display toast notifications
    function showToast(title, message, type = 'info') {
        const icons = {
            'success': 'fa-check-circle',
            'warning': 'fa-exclamation-triangle',
            'danger': 'fa-times-circle',
            'info': 'fa-info-circle'
        };
        
        const toastId = 'toast-' + Date.now();
        const html = `
            <div class="toast" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fa-solid ${icons[type]} me-2 text-${type}"></i>
                    <strong class="me-auto">${title}</strong>
                    <small>Just now</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        
        $('.toast-container').append(html);
        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, {
            animation: true,
            autohide: true,
            delay: 5000
        });
        toast.show();
        
        // Remove toast element after it's hidden
        toastEl.addEventListener('hidden.bs.toast', function() {
            $(toastEl).remove();
        });
    }
    
    // Initialize chart
    function initOrdersChart() {
        const ctx = document.getElementById('ordersChart').getContext('2d');
        
        // Sample data for the chart
        const labels = Array.from({length: 30}, (_, i) => {
            const d = new Date();
            d.setDate(d.getDate() - (29 - i));
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        
        const ordersData = generateRandomData(30, 5, 15);
        const revenueData = ordersData.map(value => value * (Math.floor(Math.random() * 30) + 15));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Orders',
                        data: ordersData,
                        borderColor: '#96588a',
                        backgroundColor: 'rgba(150, 88, 138, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Revenue ($)',
                        data: revenueData,
                        borderColor: '#0077b6',
                        backgroundColor: 'rgba(0, 119, 182, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        hidden: true
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label === 'Revenue ($)') {
                                    return label + ': $' + context.parsed.y.toFixed(2);
                                }
                                return label + ': ' + context.parsed.y;
                            }
                        }
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
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
    
    // Helper function to generate random data with a trend
    function generateRandomData(length, min, max) {
        const baseValue = Math.floor(Math.random() * (max - min)) + min;
        return Array.from({length}, (_, i) => {
            // Create a slight upward trend
            const trend = i / length * 10;
            const noise = (Math.random() - 0.5) * 5;
            return Math.max(min, Math.floor(baseValue + trend + noise));
        });
    }
    
    // Add hover effects to product rows
    $('.table tbody tr').hover(
        function() {
            $(this).addClass('bg-light');
        },
        function() {
            $(this).removeClass('bg-light');
        }
    );
});
