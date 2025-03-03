<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printify Sync Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Printify<span>Sync</span></h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin.php?page=printify-sync-dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="admin.php?page=printify-sync-products"><i class="fas fa-shopping-cart"></i> Products</a></li>
                    <li><a href="admin.php?page=printify-sync-sync"><i class="fas fa-exchange-alt"></i> Syncs</a></li>
                    <li class="active"><a href="admin.php?page=printify-sync-orders"><i class="fas fa-truck"></i> Orders</a></li>
                    <li><a href="admin.php?page=printify-sync-shops"><i class="fas fa-store"></i> Shops</a></li>
                    <li><a href="admin.php?page=printify-sync-settings"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <p>v1.2.0</p>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="top-header">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search orders...">
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="far fa-clock"></i>
                        <span id="current-datetime">2025-03-02 18:48:57 UTC</span>
                    </div>
                    <div class="user-profile">
                        <span>Welcome, ApolloWeb</span>
                        <div class="avatar">AW</div>
                    </div>
                </div>
            </header>
            
            <div class="dashboard-content">
                <div class="page-header">
                    <h1 class="page-title">Orders</h1>
                    <div class="page-actions">
                        <button class="btn-secondary"><i class="fas fa-filter"></i> Filter</button>
                        <button class="btn-primary"><i class="fas fa-sync-alt"></i> Sync Orders</button>
                    </div>
                </div>
                
                <div class="order-stats">
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Orders</h3>
                            <p class="stat-value">342</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Completed</h3>
                            <p class="stat-value">278</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Processing</h3>
                            <p class="stat-value">45</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Pending</h3>
                            <p class="stat-value">19</p>
                        </div>
                    </div>
                </div>
                
                <div class="data-card wide">
                    <div class="card-header">
                        <h3>Order List</h3>
                        <div class="card-actions">
                            <div class="select-container">
                                <select id="bulk-actions">
                                    <option value="">Bulk Actions</option>
                                    <option value="sync">Sync Selected</option>
                                    <option value="mark-complete">Mark as Complete</option>
                                    <option value="mark-processing">Mark as Processing</option>
                                    <option value="delete">Delete Selected</option>
                                </select>
                                <button class="btn-secondary small">Apply</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="data-table orders-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Shipping</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="checkbox" class="order-select"></td>
                                    <td>#ORD-7829</td>
                                    <td>John Smith</td>
                                    <td>3 items</td>
                                    <td><span class="status-badge complete">Complete</span></td>
                                    <td>2025-03-01</td>
                                    <td>$129.99</td>
                                    <td>Standard</td>
                                    <td class="actions">
                                        <button class="btn-icon"><i class="fas fa-sync-alt"></i></button>
                                        <button class="btn-icon"><i class="fas fa-eye"></i></button>
                                        <button class="btn-icon"><i class="fas fa-ellipsis-v"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="order-select"></td>
                                    <td>#ORD-7830</td>
                                    <td>Sarah Johnson</td>
                                    <td>1 item</td>
                                    <td><span class="status-badge pending">Pending</span></td>
                                    <td>2025-03-01</td>
                                    <td>$59.99</td>
                                    <td>Express</td>
                                    <td class="actions">
                                        <button class="btn-icon"><i class="fas fa-sync-alt"></i></button>
                                        <button class="btn-icon"><i class="fas fa-eye"></i></button>
                                        <button class="btn-icon"><i class="fas fa-ellipsis-v"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="order-select"></td>
                                    <td>#ORD-7831</td>
                                    <td>Michael Davis</td>
                                    <td>2 items</td>
                                    <td><span class="status-badge processing">Processing</span></td>
                                    <td>2025-03-02</td>
                                    <td>$89.98</td>
                                    <td>Standard</td>
                                    <td class="actions">
                                        <button class="btn-icon"><i class="fas fa-sync-alt"></i></button>
                                        <button class="btn-icon"><i class="fas fa-eye"></i></button>
                                        <button class="btn-icon"><i class="fas fa-ellipsis-v"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="order-select"></td>
                                    <td>#ORD-7832</td>
                                    <td>Emily Wilson</td>
                                    <td>4 items</td>
                                    <td><span class="status-badge complete">Complete</span></td>
                                    <td>2025-03-02</td>
                                    <td>$215.96</td>
                                    <td>Express</td>
                                    <td class="actions">
                                        <button class="btn-icon"><i class="fas fa-sync-alt"></i></button>
                                        <button class="btn-icon"><i class="fas fa-eye"></i></button>
                                        <button class="btn-icon"><i class="fas fa-ellipsis-v"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="order-select"></td>
                                    <td>#ORD-7833</td>
                                    <td>Robert Johnson</td>
                                    <td>2 items</td>
                                    <td><span class="status-badge processing">Processing</span></td>
                                    <td>2025-03-02</td>
                                    <td>$78.50</td>
                                    <td>Standard</td>
                                    <td class="actions">
                                        <button class="btn-icon"><i class="fas fa-sync-alt"></i></button>
                                        <button class="btn-icon"><i class="fas fa-eye"></i></button>
                                        <button class="btn-icon"><i class="fas fa-ellipsis-v"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="pagination">
                            <button class="btn-pagination disabled"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn-pagination active">1</button>
                            <button class="btn-pagination">2</button>
                            <button class="btn-pagination">3</button>
                            <span class="pagination-ellipsis">...</span>
                            <button class="btn-pagination">8</button>
                            <button class="btn-pagination"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Set current datetime
        document.getElementById('current-datetime').innerText = "2025-03-02 18:48:57 UTC";
        
        // Select all functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.order-select');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    </script>
</body>
</html>