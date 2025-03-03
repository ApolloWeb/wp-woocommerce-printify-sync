<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printify Sync Products</title>
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
                    <li class="active"><a href="admin.php?page=printify-sync-products"><i class="fas fa-shopping-cart"></i> Products</a></li>
                    <li><a href="admin.php?page=printify-sync-sync"><i class="fas fa-exchange-alt"></i> Syncs</a></li>
                    <li><a href="admin.php?page=printify-sync-orders"><i class="fas fa-truck"></i> Orders</a></li>
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
                    <input type="text" placeholder="Search products...">
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="far fa-clock"></i>
                        <span id="current-datetime">2025-03-02 18:38:03 UTC</span>
                    </div>
                    <div class="user-profile">
                        <span>Welcome, ApolloWeb</span>
                        <div class="avatar">AW</div>
                    </div>
                </div>
            </header>
            
            <div class="dashboard-content">
                <div class="page-header">
                    <h1 class="page-title">Products</h1>
                    <div class="page-actions">
                        <button class="btn-secondary"><i class="fas fa-filter"></i> Filter</button>
                        <button class="btn-primary"><i class="fas fa-plus"></i> Import Product</button>
                    </div>
                </div>
                
                <div class="product-stats">
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Products</h3>
                            <p class="stat-value">867</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Published</h3>
                            <p class="stat-value">742</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Draft</h3>
                            <p class="stat-value">125</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Pending Sync</h3>
                            <p class="stat-value">23</p>
                        </div>
                    </div>
                </div>
                
                <div class="data-card wide">
                    <div class="card-header">
                        <h3>Product List</h3>
                        <div class="card-actions">
                            <div class="select-container">
                                <select id="bulk-actions">
                                    <option value="">Bulk Actions</option>
                                    <option value="sync">Sync Selected</option>
                                    <option value="publish">Publish Selected</option>
                                    <option value="draft">Set to Draft</option>
                                    <option value="delete">Delete Selected</option>
                                </select>
                                <button class="btn-secondary small">Apply</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="data-table products-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>SKU</th>
                                    <th>Status</th>
                                    <th>Variants</th>
                                    <th>Price</th>
                                    <th>Last Synced</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="checkbox" class="product-select"></td>
                                    <td><div class="product-thumbnail"><img src="https://via.placeholder.com/50" alt="Product"></div></td>
                                    <td>Classic Purple T-Shirt</td>
                                    <td>TS-001-PRP</td>
                                    <td><span class="status-badge complete">Published</span></td>
                                    <td>6</td>
                                    <td>$24.99</td>
                                    <td>2025-03-01 14:32</td>
                                    <td class="actions">
                                        <button class="btn-icon"><i class="fas fa-sync-alt"></i></button>
                                        <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon"><i class="fas fa-ellipsis-v"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="product-select"></td>
                                    <td><div class="product-thumbnail"><img src="https://via.placeholder.com/50" alt="Product"></div></td>
                                    <td>Floral Pattern Mug</td>
                                    <td>MG-102-FLR</td>
                                    <td><span class="status-badge complete">Published</span></td>
                                    <td>2</td>
                                    <td>$18.50</td>
                                    <td>2025-03-01 12:15</td>
                                    <td class="actions">
                                        <button class="btn-icon"><i class="fas fa-sync-alt"></i></button>
                                        <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon"><i class="fas fa-ellipsis-v"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="product-select"></td>
                                    <td><div class="product-thumbnail"><img src="https://via.placeholder.com/50" alt="Product"></div></td>
                                    <td>Vintage Phone Case</td>
                                    <td>PC-203-VTG</td>
                                    <td><span class="status-badge pending">Draft</span></td>
                                    <td>12</td>
                                    <td>$22.99</td>
                                    <td>2025-03-02 09:42</td>
                                    <td class="actions">
                                        <button class="btn-icon"><i class="fas fa-sync-alt"></i></button>
                                        <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon"><i class="fas fa-ellipsis-v"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="product-select"></td>
                                    <td><div class="product-thumbnail"><img src="https://via.placeholder.com/50" alt="Product"></div></td>
                                    <td>Custom Canvas Print</td>
                                    <td>CV-405-CST</td>
                                    <td><span class="status-badge processing">Syncing</span></td>
                                    <td>1</td>
                                    <td>$45.00</td>
                                    <td>2025-03-02 11:17</td>
                                    <td class="actions">
                                        <button class="btn-icon"><i class="fas fa-sync-alt"></i></button>
                                        <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon"><i class="fas fa-ellipsis-v"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="product-select"></td>
                                    <td><div class="product-thumbnail"><img src="https://via.placeholder.com/50" alt="Product"></div></td>
                                    <td>Eco-Friendly Water Bottle</td>
                                    <td>WB-506-ECO</td>
                                    <td><span class="status-badge complete">Published</span></td>
                                    <td>4</td>
                                    <td>$29.95</td>
                                    <td>2025-03-01 18:03</td>
                                    <td class="actions">
                                        <button class="btn-icon"><i class="fas fa-sync-alt"></i></button>
                                        <button class="btn-icon"><i class="fas fa-edit"></i></button>
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
                            <button class="btn-pagination">12</button>
                            <button class="btn-pagination"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Set current datetime
        document.getElementById('current-datetime').innerText = "2025-03-02 18:38:03 UTC";
    </script>
</body>
</html>