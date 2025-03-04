<<<<<<< HEAD
<?php
/**
 * Stock Levels Pie Chart Widget
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Sample data for the stock levels chart - would be replaced with real data
$stock_data = isset($stock_data) ? $stock_data : [
    'in_stock' => 186,
    'low_stock' => 42,
    'out_of_stock' => 20
];

// Calculate total and percentages
$total_products = array_sum($stock_data);
$percent_in_stock = $total_products > 0 ? round(($stock_data['in_stock'] / $total_products) * 100) : 0;
$percent_low_stock = $total_products > 0 ? round(($stock_data['low_stock'] / $total_products) * 100) : 0;
$percent_out_of_stock = $total_products > 0 ? round(($stock_data['out_of_stock'] / $total_products) * 100) : 0;

// Determine alert level
$alert_level = 'good';
if ($percent_out_of_stock > 15) {
    $alert_level = 'critical';
} elseif ($percent_low_stock > 25 || $percent_out_of_stock > 5) {
    $alert_level = 'warning';
}
?>

<div class="stock-levels-container">
    <div class="stock-chart-container">
        <canvas id="stockLevelsChart"></canvas>
    </div>
    
    <div class="stock-metrics">
        <div class="stock-summary">
            <div class="stock-metric <?php echo $alert_level; ?>">
                <span class="metric-value"><?php echo $total_products; ?></span>
                <span class="metric-label">Total Products</span>
            </div>
        </div>
        
        <div class="stock-details">
            <div class="stock-detail-item in-stock">
                <span class="stock-indicator"></span>
                <div class="stock-detail-info">
                    <span class="stock-detail-count"><?php echo $stock_data['in_stock']; ?></span>
                    <span class="stock-detail-label">In Stock</span>
                    <span class="stock-detail-percent"><?php echo $percent_in_stock; ?>%</span>
                </div>
            </div>
            
            <div class="stock-detail-item low-stock">
                <span class="stock-indicator"></span>
                <div class="stock-detail-info">
                    <span class="stock-detail-count"><?php echo $stock_data['low_stock']; ?></span>
                    <span class="stock-detail-label">Low Stock</span>
                    <span class="stock-detail-percent"><?php echo $percent_low_stock; ?>%</span>
                </div>
            </div>
            
            <div class="stock-detail-item out-of-stock">
                <span class="stock-indicator"></span>
                <div class="stock-detail-info">
                    <span class="stock-detail-count"><?php echo $stock_data['out_of_stock']; ?></span>
                    <span class="stock-detail-label">Out of Stock</span>
                    <span class="stock-detail-percent"><?php echo $percent_out_of_stock; ?>%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="stock-actions">
    <a href="<?php echo admin_url('admin.php?page=printify-stock'); ?>" class="printify-btn btn-sm">
        <i class="fas fa-cube"></i> Manage Inventory
    </a>
    <button id="syncStockNow" class="printify-btn btn-outline btn-sm">
        <i class="fas fa-sync"></i> Sync Stock Now
    </button>
</div>

<style>
.stock-levels-container {
    display: flex;
    align-items: center;
}

.stock-chart-container {
    width: 50%;
    position: relative;
}

.stock-metrics {
    width: 50%;
    padding-left: 20px;
}

.stock-summary {
    margin-bottom: 15px;
}

.stock-metric {
    display: inline-block;
    padding: 10px 15px;
    border-radius: 4px;
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
}

.stock-metric.good {
    border-left: 4px solid #46b450;
}

.stock-metric.warning {
    border-left: 4px solid #ffb900;
}

.stock-metric.critical {
    border-left: 4px solid #dc3232;
}

.metric-value {
    font-size: 24px;
    font-weight: 600;
    display: block;
}

.metric-label {
    font-size: 12px;
    color: #6c757d;
}

.stock-details {
    margin-top: 15px;
}

.stock-detail-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.stock-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 10px;
}

.in-stock .stock-indicator {
    background-color: #46b450;
}

.low-stock .stock-indicator {
    background-color: #ffb900;
}

.out-of-stock .stock-indicator {
    background-color: #dc3232;
}

.stock-detail-info {
    display: flex;
    align-items: center;
}

.stock-detail-count {
    font-weight: 600;
    width: 40px;
}

.stock-detail-label {
    flex: 1;
    padding-right: 10px;
}

.stock-detail-percent {
    font-weight: 600;
    color: #212529;
    background: #f8f9fa;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 12px;
    min-width: 36px;
    text-align: center;
}

.stock-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
    border-top: 1px solid #e9ecef;
    padding-top: 15px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load Chart.js from CDN if not already loaded
    if (typeof Chart === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
        script.onload = initStockChart;
        document.head.appendChild(script);
    } else {
        initStockChart();
    }
    
    function initStockChart() {
        const ctx = document.getElementById('stockLevelsChart').getContext('2d');
        
        // Stock data from PHP
        const stockData = {
            labels: ['In Stock', 'Low Stock', 'Out of Stock'],
            datasets: [{
                data: [
                    <?php echo $stock_data['in_stock']; ?>,
                    <?php echo $stock_data['low_stock']; ?>,
                    <?php echo $stock_data['out_of_stock']; ?>
                ],
                backgroundColor: [
                    '#46b450',  // Green
                    '#ffb900',  // Amber
                    '#dc3232'   // Red
                ],
                borderWidth: 1,
                borderColor: '#ffffff'
            }]
        };
        
        // Create the pie chart
        const stockChart = new Chart(ctx, {
            type: 'doughnut',
            data: stockData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        display: false
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
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        
        // Set up stock sync button
        document.getElementById('syncStockNow').addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
            this.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-check"></i> Sync Complete';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-sync"></i> Sync Stock Now';
                    this.disabled = false;
                }, 2000);
            }, 1500);
        });
    }
});
</script>
=======
<div class="widget-content">
    <h3>Stock Levels</h3>
    <canvas id="stock-levels-chart"></canvas>
</div>

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: </div>
#
#
# Commit Hash 16c804f
#
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
