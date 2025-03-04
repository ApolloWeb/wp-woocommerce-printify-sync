<?php
/**
 * Main dashboard template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.2.4
 * @date 2025-03-03 13:53:06
 */
defined('ABSPATH') || exit;

// Get current user info
$current_user = 'ApolloWeb';
?>
<div class="wrap printify-sync-dashboard">
    <header class="top-header">
        <div class="header-left">
            <div class="logo-container">
                <h2 class="site-logo"><i class="fab fa-print"></i> Printify<span>Sync</span></h2>
            </div>
            <?php 
            // Include navigation component if it exists
            $nav_component = plugin_dir_path(__FILE__) . 'components/navigation.php';
            if (file_exists($nav_component)) {
                include $nav_component;
            } else {
                echo '<div class="main-nav"><ul><li class="active"><a href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li></ul></div>';
            }
            ?>
        </div>
        
        <div class="header-right">
            <div class="user-profile">
                <span>Welcome, <?php echo esc_html($current_user); ?></span>
                <div class="avatar">AW</div>
            </div>
        </div>
    </header>
    
    <div class="dashboard-content">
        <!-- Stats Cards with Font Awesome Icons -->
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
                    <i class="fas fa-tshirt"></i>
                </div>
                <div class="stat-details">
                    <h3>Active Products</h3>
                    <p class="stat-value">867</p>
                    <p class="stat-change positive"><i class="fas fa-arrow-up"></i> 8.3% from last month</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <div class="stat-details">
                    <h3>Orders Processed</h3>
                    <p class="stat-value">342</p>
                    <p class="stat-change positive"><i class="fas fa-arrow-up"></i> 5.7% from yesterday</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="stat-details">
                    <h3>Open Tickets</h3>
                    <p class="stat-value">24</p>
                    <p class="stat-change negative"><i class="fas fa-arrow-down"></i> 3.2% from last week</p>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="chart-grid">
            <!-- Sales Chart -->
            <div class="chart-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Sales Overview</h3>
                    <div class="card-actions">
                        <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                        <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="sales-chart" width="400" height="250"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Categories Chart -->
            <div class="chart-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Product Categories</h3>
                    <div class="card-actions">
                        <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                        <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="product-categories-chart" width="400" height="250"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Progress Circle -->
            <div class="chart-card">
                <div class="card-header">
                    <h3><i class="fas fa-check-circle"></i> Sync Success Rate</h3>
                    <div class="card-actions">
                        <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                        <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                    </div>
                </div>
                <div class="card-body progress-container">
                    <!-- Single progress circle container -->
                    <div class="sync-progress-wrapper">
                        <div id="sync-success-progress" style="width:150px;height:150px;margin:0 auto;"></div>
                        <div class="progress-info">
                            <h4>98.2%</h4>
                            <p>Success rate</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- API Performance -->
            <div class="chart-card">
                <div class="card-header">
                    <h3><i class="fas fa-server"></i> API Performance</h3>
                    <div class="card-actions">
                        <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
                        <button class="btn-transparent"><i class="fas fa-ellipsis-v"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="api-performance-chart" width="400" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tables Section -->
        <div class="data-tables-container">
            <!-- Recent Orders Table -->
            <div class="data-card">
                <div class="card-header">
                    <h3><i class="fas fa-shopping-cart"></i> Last 5 Orders Added/Updated</h3>
                    <div class="card-actions">
                        <button class="btn-primary"><i class="fas fa-eye"></i> View All Orders</button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Products</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#ORD-7835</td>
                                <td>Emily Johnson</td>
                                <td><span class="status-badge processing">Processing</span></td>
                                <td>2 items</td>
                                <td>$89.98</td>
                                <td>2025-03-03 10:45:22</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view" title="View Order"><i class="fas fa-eye"></i></button>
                                        <button class="btn-action edit" title="Edit Order"><i class="fas fa-pencil-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-7834</td>
                                <td>Robert Williams</td>
                                <td><span class="status-badge complete">Complete</span></td>
                                <td>1 item</td>
                                <td>$34.99</td>
                                <td>2025-03-02 17:22:15</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view" title="View Order"><i class="fas fa-eye"></i></button>
                                        <button class="btn-action edit" title="Edit Order"><i class="fas fa-pencil-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-7833</td>
                                <td>Sarah Thompson</td>
                                <td><span class="status-badge pending">Pending</span></td>
                                <td>3 items</td>
                                <td>$125.50</td>
                                <td>2025-03-02 14:05:37</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view" title="View Order"><i class="fas fa-eye"></i></button>
                                        <button class="btn-action edit" title="Edit Order"><i class="fas fa-pencil-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-7832</td>
                                <td>James Miller</td>
                                <td><span class="status-badge complete">Complete</span></td>
                                <td>2 items</td>
                                <td>$58.75</td>
                                <td>2025-03-01 09:18:42</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view" title="View Order"><i class="fas fa-eye"></i></button>
                                        <button class="btn-action edit" title="Edit Order"><i class="fas fa-pencil-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-7831</td>
                                <td>Michelle Davis</td>
                                <td><span class="status-badge complete">Complete</span></td>
                                <td>1 item</td>
                                <td>$45.99</td>
                                <td>2025-03-01 08:32:10</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view" title="View Order"><i class="fas fa-eye"></i></button>
                                        <button class="btn-action edit" title="Edit Order"><i class="fas fa-pencil-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Products Table (Fixed) -->
            <div class="data-card">
                <div class="card-header">
                    <h3><i class="fas fa-tshirt"></i> Last 5 Products Added/Updated</h3>
                    <div class="card-actions">
                        <button class="btn-primary"><i class="fas fa-eye"></i> View All Products</button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#PRD-5428</td>
                                <td>Custom Logo T-Shirt</td>
                                <td>T-Shirts</td>
                                <td><span class="status-badge active">Active</span></td>
                                <td>$24.99</td>
                                <td>2025-03-03 11:45:18</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view" title="View Product"><i class="fas fa-eye"></i></button>
                                        <button class="btn-action edit" title="Edit Product"><i class="fas fa-pencil-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#PRD-5427</td>
                                <td>Geometric Pattern Mug</td>
                                <td>Mugs</td>
                                <td><span class="status-badge active">Active</span></td>
                                <td>$19.99</td>
                                <td>2025-03-02 16:30:45</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view" title="View Product"><i class="fas fa-eye"></i></button>
                                        <button class="btn-action edit" title="Edit Product"><i class="fas fa-pencil-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#PRD-5426</td>
                                <td>Minimalist Art Poster</td>
                                <td>Posters</td>
                                <td><span class="status-badge draft">Draft</span></td>
                                <td>$15.50</td>
                                <td>2025-03-02 14:22:30</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view" title="View Product"><i class="fas fa-eye"></i></button>
                                        <button class="btn-action edit" title="Edit Product"><i class="fas fa-pencil-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#PRD-5425</td>
                                <td>Custom Photo Phone Case</td>
                                <td>Phone Cases</td>
                                <td><span class="status-badge active">Active</span></td>
                                <td>$29.95</td>
                                <td>2025-03-02 09:10:05</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view" title="View Product"><i class="fas fa-eye"></i></button>
                                        <button class="btn-action edit" title="Edit Product"><i class="fas fa-pencil-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#PRD-5424</td>
                                <td>Vintage Logo Hoodie</td>
                                <td>Hoodies</td>
                                <td><span class="status-badge inactive">Inactive</span></td>
                                <td>$42.99</td>
                                <td>2025-03-01 15:05:27</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view" title="View Product"><i class="fas fa-eye"></i></button>
                                        <button class="btn-action edit" title="Edit Product"><i class="fas fa-pencil-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>