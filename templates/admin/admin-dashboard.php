<?php
/**
 * Admin Dashboard Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get current user info
$current_user = function_exists('printify_sync_get_current_user') ? 
    printify_sync_get_current_user() : 'ApolloWeb';
    
$current_datetime = function_exists('printify_sync_get_current_datetime') ?
    printify_sync_get_current_datetime() : '2025-03-04 10:39:02';
?>
<div class="printify-dashboard-page">
    
    <?php 
    // Include the navigation
    if (file_exists(PRINTIFY_SYNC_PATH . 'templates/admin/navigation.php')) {
        include PRINTIFY_SYNC_PATH . 'templates/admin/navigation.php';
    }
    ?>
    
    <div class="printify-content">
        <div class="stats-boxes">
            <div class="stat-box">
                <div class="icon"><i class="fas fa-store"></i></div>
                <div class="label">Active Shops</div>
                <div class="value">3</div>
                <div class="change"><i class="fas fa-arrow-up"></i> +1 this month</div>
            </div>
            
            <div class="stat-box">
                <div class="icon"><i class="fas fa-shirt"></i></div>
                <div class="label">Synced Products</div>
                <div class="value">248</div>
                <div class="change"><i class="fas fa-arrow-up"></i> +37 this week</div>
            </div>
            
            <div class="stat-box">
                <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="label">Recent Orders</div>
                <div class="value">18</div>
                <div class="change"><i class="fas fa-arrow-up"></i> +5 today</div>
            </div>
            
            <div class="stat-box">
                <div class="icon"><i class="fas fa-sync"></i></div>
                <div class="label">Last Sync</div>
                <div class="value">2 hrs ago</div>
                <div class="change">Successful</div>
            </div>
        </div>
        
        <!-- Sales Graph Widget -->
        <div class="dashboard-widget sales-graph-widget">
            <h3><i class="fas fa-chart-line"></i> Sales Analytics</h3>
            <div class="widget-content" id="sales-graph-widget">
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="sales-filter">
                    <button class="filter-btn" data-period="day">Day</button>
                    <button class="filter-btn" data-period="week">Week</button>
                    <button class="filter-btn" data-period="month">Month</button>
                    <button class="filter-btn" data-period="year">Year</button>
                </div>
            </div>
        </div>
        
        <div class="dashboard-widgets">
            <div class="widget-row">
                <div class="dashboard-widget">
                    <h3><i class="fas fa-store"></i> Shop Information</h3>
                    <div class="widget-content" id="shop-info-widget">
                        <table class="printify-table">
                            <thead>
                                <tr>
                                    <th>Shop</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>T-Shirt Store</td>
                                    <td>145</td>
                                    <td><span class="status-badge success">Connected</span></td>
                                </tr>
                                <tr>
                                    <td>Mug Shop</td>
                                    <td>35</td>
                                    <td><span class="status-badge success">Connected</span></td>
                                </tr>
                                <tr>
                                    <td>Home Decor</td>
                                    <td>68</td>
                                    <td><span class="status-badge success">Connected</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="dashboard-widget">
                    <h3><i class="fas fa-sync"></i> Product Sync Summary</h3>
                    <div class="widget-content" id="product-sync-widget">
                        <table class="printify-table">
                            <thead>
                                <tr>
                                    <th>Product Type</th>
                                    <th>Synced</th>
                                    <th>Pending</th>
                                    <th>Last Sync</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>T-Shirts</td>
                                    <td>121</td>
                                    <td>0</td>
                                    <td>2 hours ago</td>
                                </tr>
                                <tr>
                                    <td>Hoodies</td>
                                    <td>48</td>
                                    <td>5</td>
                                    <td>3 hours ago</td>
                                </tr>
                                <tr>
                                    <td>Mugs</td>
                                    <td>35</td>
                                    <td>0</td>
                                    <td>2 hours ago</td>
                                </tr>
                                <tr>
                                    <td>Posters</td>
                                    <td>44</td>
                                    <td>12</td>
                                    <td>5 hours ago</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="widget-row">
                <div class="dashboard-widget">
                    <h3><i class="fas fa-shopping-cart"></i> Recent Orders</h3>
                    <div class="widget-content" id="orders-overview-widget">
                        <table class="printify-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#1245</td>
                                    <td>Today</td>
                                    <td><span class="status-badge info">Processing</span></td>
                                    <td>$42.99</td>
                                </tr>
                                <tr>
                                    <td>#1244</td>
                                    <td>Today</td>
                                    <td><span class="status-badge success">Completed</span></td>
                                    <td>$32.50</td>
                                </tr>
                                <tr>
                                    <td>#1243</td>
                                    <td>Yesterday</td>
                                    <td><span class="status-badge warning">On Hold</span></td>
                                    <td>$58.99</td>
                                </tr>
                                <tr>
                                    <td>#1242</td>
                                    <td>Yesterday</td>
                                    <td><span class="status-badge success">Completed</span></td>
                                    <td>$29.99</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="dashboard-widget">
                    <h3><i class="fas fa-bell"></i> Webhook Status</h3>
                    <div class="widget-content" id="webhook-status-widget">
                        <div class="webhook-status">
                            <div class="webhook-status-item">
                                <div class="webhook-name">Product Updates</div>
                                <div class="webhook-indicator active">
                                    <i class="fas fa-check-circle"></i> Active
                                </div>
                            </div>
                            <div class="webhook-status-item">
                                <div class="webhook-name">Order Status</div>
                                <div class="webhook-indicator active">
                                    <i class="fas fa-check-circle"></i> Active
                                </div>
                            </div>
                            <div class="webhook-status-item">
                                <div class="webhook-name">Inventory Updates</div>
                                <div class="webhook-indicator error">
                                    <i class="fas fa-times-circle"></i> Error
                                </div>
                                <div class="webhook-error-message">
                                    Last error