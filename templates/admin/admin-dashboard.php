<?php
/**
 * Main dashboard template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */
defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printify Sync Dashboard</title>
</head>
<body>
    <div class="dashboard no-sidebar">
        <main class="main-content full-width">
            <header class="top-header">
                <div class="header-left">
                    <div class="logo-container">
                        <h2 class="site-logo">Printify<span>Sync</span></h2>
                    </div>
                    <?php include plugin_dir_path(__FILE__) . 'components/navigation.php'; ?>
                </div>
                
                <div class="header-right">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search...">
                    </div>
                    <div class="user-profile">
                        <span>Welcome, ApolloWeb</span>
                        <div class="avatar">AW</div>
                    </div>
                </div>
            </header>
            
            <div class="dashboard-content">
                <div class="quick-stats">
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Product Syncs</h3>
                            <p class="stat-value">1,248</p>
                            <p class="stat-change positive"><i class="fas fa-arrow-up"></i> 12.5% from last week</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon pink">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Active Products</h3>
                            <p class="stat-value">867</p>
                            <p class="stat-change positive"><i class="fas fa-arrow-up"></i> 8.3% from last month</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Orders Processed</h3>
                            <p class="stat-value">342</p>
                            <p class="stat-change positive"><i class="fas fa-arrow-up"></i> 5.7% from yesterday</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Open Tickets</h3>
                            <p class="stat-value">24</p>
                            <p class="stat-change negative"><i class="fas fa-arrow-down"></i> 3.2% from last week</p>
                        </div>
                    </div>
                </div>
                
                <div class="chart-grid">
                    <div class="chart-card">
                        <div class="card-header">
                            <h3>Sales Overview</h3>
                            <div class="card-actions">
                                <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="sales-chart" height="250"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-card">
                        <div class="card-header">
                            <h3>Product Categories</h3>
                            <div class="card-actions">
                                <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                            </div>
                        </div>
                        <div class="card-body pie-chart-container">
                            <canvas id="product-categories-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-card">
                        <div class="card-header">
                            <h3>Sync Success Rate</h3>
                            <div class="card-actions">
                                <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                            </div>
                        </div>
                        <div class="card-body progress-container">
                            <div id="sync-success-progress"></div>
                            <div class="progress-info">
                                <h4>98.2%</h4>
                                <p>Success rate</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-card">
                        <div class="card-header">
                            <h3>API Performance</h3>
                            <div class="card-actions">
                                <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="api-performance-chart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="data-grid">
                    <div class="data-card wide">
                        <div class="card-header">
                            <h3>Recent Orders</h3>
                            <div class="card-actions">
                                <button class="btn-primary">View All</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Products</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>#ORD-7829</td>
                                        <td>John Smith</td>
                                        <td>3 items</td>
                                        <td><span class="status-badge complete">Complete</span></td>
                                        <td>2025-03-01</td>
                                        <td>$129.99</td>
                                    </tr>
                                    <tr>
                                        <td>#ORD-7830</td>
                                        <td>Sarah Johnson</td>
                                        <td>1 item</td>
                                        <td><span class="status-badge pending">Pending</span></td>
                                        <td>2025-03-01</td>
                                        <td>$59.99</td>
                                    </tr>
                                    <tr>
                                        <td>#ORD-7831</td>
                                        <td>Michael Davis</td>
                                        <td>2 items</td>
                                        <td><span class="status-badge processing">Processing</span></td>
                                        <td>2025-03-02</td>
                                        <td>$89.98</td>
                                    </tr>
                                    <tr>
                                        <td>#ORD-7832</td>
                                        <td>Emily Wilson</td>
                                        <td>4 items</td>
                                        <td><span class="status-badge complete">Complete</span></td>
                                        <td>2025-03-03</td>
                                        <td>$215.96</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="data-card">
                        <div class="card-header">
                            <h3>Webhook Status</h3>
                            <div class="card-actions">
                                <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="webhook-status">
                                <div class="webhook-item">
                                    <div class="webhook-name">Product Updates</div>
                                    <div class="webhook-indicator active"></div>
                                </div>
                                <div class="webhook-item">
                                    <div class="webhook-name">Order Status</div>
                                    <div class="webhook-indicator active"></div>
                                </div>
                                <div class="webhook-item">
                                    <div class="webhook-name">Inventory Changes</div>
                                    <div class="webhook-indicator inactive"></div>
                                </div>
                                <div class="webhook-item">
                                    <div class="webhook-name">Price Updates</div>
                                    <div class="webhook-indicator active"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="data-card">
                        <div class="card-header">
                            <h3>Recent Activity</h3>
                            <div class="card-actions">
                                <button class="btn-transparent"><i class="fas fa-filter"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="activity-stream">
                                <div class="activity-item">
                                    <div class="activity-icon purple">
                                        <i class="fas fa-sync-alt"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p class="activity-text">Product sync completed</p>
                                        <span class="activity-time">10 minutes ago</span>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon blue">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p class="activity-text">New order received - #ORD-7832</p>
                                        <span class="activity-time">32 minutes ago</span>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon green">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p class="activity-text">Stock levels updated</p>
                                        <span class="activity-time">1 hour ago</span>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon orange">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p class="activity-text">API rate limit warning</p>
                                        <span class="activity-time">2 hours ago</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>