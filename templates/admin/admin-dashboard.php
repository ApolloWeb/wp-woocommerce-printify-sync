<?php
/**
 * Main dashboard template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.0.7
 */
defined('ABSPATH') || exit;

// Get the plugin path for includes
$plugin_path = plugin_dir_path(__FILE__);
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
                    <?php include $plugin_path . 'components/navigation.php'; ?>
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
                    <!-- Sales Chart Card -->
                    <div class="chart-card">
                        <div class="card-header">
                            <h3>Sales Overview</h3>
                            <div class="card-actions">
                                <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:250px;">
                                <canvas id="sales-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Categories Chart Card -->
                    <div class="chart-card">
                        <div class="card-header">
                            <h3>Product Categories</h3>
                            <div class="card-actions">
                                <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:250px;">
                                <canvas id="product-categories-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sync Success Chart Card -->
                    <div class="chart-card">
                        <div class="card-header">
                            <h3>Sync Success Rate</h3>
                            <div class="card-actions">
                                <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                            </div>
                        </div>
                        <div class="card-body progress-container">
                            <div id="sync-success-progress" style="width:150px;height:150px;margin:0 auto;"></div>
                            <div class="progress-info">
                                <h4>98.2%</h4>
                                <p>Success rate</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- API Performance Chart Card -->
                    <div class="chart-card">
                        <div class="card-header">
                            <h3>API Performance</h3>
                            <div class="card-actions">
                                <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                                <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:250px;">
                                <canvas id="api-performance-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Data Grid Section -->
                <div class="data-grid">
                    <!-- More dashboard content... -->
                </div>
            </div>
        </main>
    </div>
    
    <!-- Debug Info - hidden from users -->
    <!-- Template Version: 1.0.7 -->
    <!-- Time: 2025-03-03 11:45:42 -->
    <!-- User: ApolloWeb -->
</body>
</html>