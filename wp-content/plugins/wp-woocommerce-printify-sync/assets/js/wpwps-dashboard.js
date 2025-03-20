jQuery(document).ready(function($) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    let salesChart = null;

    const chartData = {
        day: {
            labels: ['12am', '4am', '8am', '12pm', '4pm', '8pm'],
            data: [5, 10, 15, 25, 20, 30]
        },
        week: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            data: [150, 120, 140, 180, 200, 220, 190]
        },
        month: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            data: [800, 950, 1100, 1200]
        },
        year: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            data: [9500, 10200, 11800, 12500, 11900, 13100, 12800, 13500, 14200, 14800, 15100, 16200]
        }
    };

    function formatCurrency(value) {
        // If window.formatCurrency is available, use it, otherwise use local implementation
        if (window.formatCurrency) {
            return window.formatCurrency(value);
        } else {
            // Local implementation
            const numAmount = parseFloat(value);
            if (isNaN(numAmount)) return 'N/A';
            
            // Get currency and symbols from wpwps_data
            const currency = (typeof wpwps_data !== 'undefined' && wpwps_data.currency) ? wpwps_data.currency : 'GBP';
            const symbols = (typeof wpwps_data !== 'undefined' && wpwps_data.currency_symbols) ? 
                wpwps_data.currency_symbols : 
                {
                    'GBP': '£',
                    'USD': '$',
                    'EUR': '€'
                };
            
            // Check if the amount needs to be divided by 100
            const valueToFormat = numAmount.toString().includes('.') ? 
                numAmount : 
                (numAmount / 100);
                
            return symbols[currency] + valueToFormat.toFixed(2);
        }
    }

    function createChart(period) {
        if (salesChart) {
            salesChart.destroy();
        }

        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData[period].labels,
                datasets: [{
                    label: 'Sales',
                    data: chartData[period].data,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // This is crucial for responsive chart
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += formatCurrency(context.parsed.y * 100); // Convert to cents for formatter
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Initialize with monthly data
    createChart('month');

    // Handle period switches
    $('.btn-group .btn').on('click', function() {
        $('.btn-group .btn').removeClass('active');
        $(this).addClass('active');
        createChart($(this).data('period'));
    });
    
    // Add direct click handler with better debug
    console.log('Setting up clear all data handler');
    $('#clear-all-data').on('click', function(e) {
        e.preventDefault();
        console.log('Clear All Data button clicked DIRECT');
        
        if (!confirm('WARNING: This will delete ALL Printify products and orders from WooCommerce. This action cannot be undone. Continue?')) {
            console.log('First confirmation declined');
            return;
        }
        
        if (!confirm('Are you absolutely sure? All imported products and orders will be permanently deleted.')) {
            console.log('Second confirmation declined');
            return;
        }
        
        const button = $(this);
        const originalText = button.html();
        
        button.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        console.log('Sending AJAX request to clear all data...');
        console.log('AJAX URL:', wpwps_data.ajax_url);
        console.log('Nonce:', wpwps_data.nonce);
        
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'clear_all_data',
                nonce: wpwps_data.nonce
            },
            success: function(response) {
                console.log('Clear all data response:', response);
                if (response.success) {
                    alert('Success! ' + response.data.message);
                    $('<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">')
                        .html(`
                            <strong><i class="fas fa-check-circle"></i> Success!</strong> ${response.data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `)
                        .insertAfter(button.closest('.d-grid'));
                    
                    // Update product and order counts to 0
                    $('.wpwps-stat-card .h2').text('0');
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    $('<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">')
                        .html(`
                            <strong><i class="fas fa-exclamation-triangle"></i> Error!</strong> ${response.data.message || 'An unknown error occurred'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `)
                        .insertAfter(button.closest('.d-grid'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr.responseText);
                alert('Network error occurred: ' + error);
                $('<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">')
                    .html(`
                        <strong><i class="fas fa-exclamation-triangle"></i> Error!</strong> Network error occurred: ${error}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `)
                    .insertAfter(button.closest('.d-grid'));
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
});
