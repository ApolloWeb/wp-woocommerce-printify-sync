<?php
/**
 * Dashboard Sales Graph Widget
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Sample data for the graph - this would be replaced with real data
$sales_data = isset($sales_data) ? $sales_data : [
    'daily' => [
        'labels' => ["Mar 1", "Mar 2", "Mar 3", "Mar 4", "Mar 5", "Mar 6", "Mar 7"],
        'datasets' => [
            [
                'label' => 'Revenue',
                'data' => [521.95, 398.50, 427.80, 568.25, 490.30, 712.45, 635.10],
                'backgroundColor' => 'rgba(127, 84, 179, 0.2)',
                'borderColor' => '#7f54b3',
                'pointBackgroundColor' => '#7f54b3'
            ],
            [
                'label' => 'Orders',
                'data' => [8, 5, 6, 9, 7, 12, 10],
                'backgroundColor' => 'rgba(0, 194, 146, 0.2)',
                'borderColor' => '#00c292',
                'pointBackgroundColor' => '#00c292',
                'yAxisID' => 'y1'
            ]
        ]
    ],
    'weekly' => [
        'labels' => ["Week 5", "Week 6", "Week 7", "Week 8", "Week 9"],
        'datasets' => [
            [
                'label' => 'Revenue',
                'data' => [2845.75, 3256.50, 2987.30, 3512.45, 4235.10],
                'backgroundColor' => 'rgba(127, 84, 179, 0.2)',
                'borderColor' => '#7f54b3',
                'pointBackgroundColor' => '#7f54b3'
            ],
            [
                'label' => 'Orders',
                'data' => [45, 52, 47, 56, 70],
                'backgroundColor' => 'rgba(0, 194, 146, 0.2)',
                'borderColor' => '#00c292',
                'pointBackgroundColor' => '#00c292',
                'yAxisID' => 'y1'
            ]
        ]
    ],
    'monthly' => [
        'labels' => ["Oct", "Nov", "Dec", "Jan", "Feb", "Mar"],
        'datasets' => [
            [
                'label' => 'Revenue',
                'data' => [9845.75, 11256.50, 15987.30, 12512.45, 10235.10, 14356.80],
                'backgroundColor' => 'rgba(127, 84, 179, 0.2)',
                'borderColor' => '#7f54b3',
                'pointBackgroundColor' => '#7f54b3'
            ],
            [
                'label' => 'Orders',
                'data' => [156, 187, 245, 198, 165, 220],
                'backgroundColor' => 'rgba(0, 194, 146, 0.2)',
                'borderColor' => '#00c292',
                'pointBackgroundColor' => '#00c292',
                'yAxisID' => 'y1'
            ]
        ]
    ],
    'yearly' => [
        'labels' => ["2020", "2021", "2022", "2023", "2024", "2025"],
        'datasets' => [
            [
                'label' => 'Revenue',
                'data' => [68452.50, 92567.30, 115845.75, 142356.50, 175987.30, 42512.45],
                'backgroundColor' => 'rgba(127, 84, 179, 0.2)',
                'borderColor' => '#7f54b3',
                'pointBackgroundColor' => '#7f54b3'
            ],
            [
                'label' => 'Orders',
                'data' => [1050, 1425, 1788, 2256, 2789, 712],
                'backgroundColor' => 'rgba(0, 194, 146, 0.2)',
                'borderColor' => '#00c292',
                'pointBackgroundColor' => '#00c292',
                'yAxisID' => 'y1'
            ]
        ]
    ]
];

// Get current filtered period (default to monthly)
$current_period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'monthly';
if (!in_array($current_period, ['daily', 'weekly', 'monthly', 'yearly'])) {
    $current_period = 'monthly';
}
?>

<div class="sales-graph-container">
    <div class="graph-header">
        <div class="period-selector">
            <a href="<?php echo add_query_arg('period', 'daily'); ?>" class="period-filter <?php echo $current_period === 'daily' ? 'active' : ''; ?>">Daily</a>
            <a href="<?php echo add_query_arg('period', 'weekly'); ?>" class="period-filter <?php echo $current_period === 'weekly' ? 'active' : ''; ?>">Weekly</a>
            <a href="<?php echo add_query_arg('period', 'monthly'); ?>" class="period-filter <?php echo $current_period === 'monthly' ? 'active' : ''; ?>">Monthly</a>
            <a href="<?php echo add_query_arg('period', 'yearly'); ?>" class="period-filter <?php echo $current_period === 'yearly' ? 'active' : ''; ?>">Yearly</a>
        </div>
        
        <div class="graph-summary">
            <?php 
            // Calculate totals for current period
            $current_data = $sales_data[$current_period];
            $total_revenue = array_sum($current_data['datasets'][0]['data']);
            $total_orders = array_sum($current_data['datasets'][1]['data']);
            
            $avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
            ?>
            <div class="summary-item">
                <span class="summary-label">Total Revenue</span>
                <span class="summary-value">$<?php echo number_format($total_revenue, 2); ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Orders</span>
                <span class="summary-value"><?php echo $total_orders; ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Avg. Order Value</span>
                <span class="summary-value">$<?php echo number_format($avg_order_value, 2); ?></span>
            </div>
        </div>
    </div>
    
    <div class="graph-canvas-container">
        <canvas id="salesChart"></canvas>
    </div>
</div>

<style>
.sales-graph-container {
    padding: 0;
}

.graph-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 0 15px 0;
    border-bottom: 1px solid #e9ecef;
}

.period-selector {
    display: flex;
    gap: 5px;
}

.period-filter {
    padding: 5px 12px;
    font-size: 13px;
    font-weight: 500;
    color: #6c757d;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}

.period-filter:hover {
    background: #e9ecef;
    color: #495057;
}

.period-filter.active {
    background: #7f54b3;
    color: white;
    border-color: #7f54b3;
}

.graph-summary {
    display: flex;
    gap: 15px;
}

.summary-item {
    text-align: right;
}

.summary-label {
    display: block;
    font-size: 12px;
    color: #6c757d;
}

.summary-value {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
}

.graph-canvas-container {
    height: 340px;
    position: relative;
    margin-top: 15px;
}

/* Responsive adjustments */
@media (max-width: 782px) {
    .graph-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .period-selector {
        margin-bottom: 15px;
    }
    
    .graph-summary {
        width: 100%;
        justify-content: space-between;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load Chart.js from CDN if not already loaded
    if (typeof Chart === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
        script.onload = initChart;
        document.head.appendChild(script);
    } else {
        initChart();
    }
    
    function initChart() {
        // Get current period data
        const currentPeriod = '<?php echo $current_period; ?>';
        const salesData = <?php echo json_encode($sales_data[$current_period]); ?>;
        
        // Get canvas context
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        // Create the chart
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: salesData.labels,
                datasets: salesData.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#212529',
                        bodyColor: '#212529',
                        borderColor: '#e9ecef',
                        borderWidth: 1,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.label === 'Revenue') {
                                    label += '$' + context.parsed.y.toFixed(2);
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                }
            }
        });
    }
});
</script>