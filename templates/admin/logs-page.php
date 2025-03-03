<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printify Sync Log Viewer</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard no-sidebar">
        <main class="main-content full-width">
            <header class="top-header">
                <div class="header-left">
                    <div class="logo-container">
                        <h2 class="site-logo">Printify<span>Sync</span></h2>
                    </div>
                    <nav class="main-nav">
                        <ul>
                            <li><a href="admin.php?page=printify-sync-dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                            <li><a href="admin.php?page=printify-sync-products"><i class="fas fa-shopping-cart"></i> Products</a></li>
                            <li><a href="admin.php?page=printify-sync-exchange-rates"><i class="fas fa-exchange-alt"></i> Exchange Rates</a></li>
                            <li><a href="admin.php?page=printify-sync-orders"><i class="fas fa-truck"></i> Orders</a></li>
                            <li><a href="admin.php?page=printify-sync-shops"><i class="fas fa-store"></i> Shops</a></li>
                            <li class="active"><a href="admin.php?page=printify-sync-logs"><i class="fas fa-list-alt"></i> Log Viewer</a></li>
                            <li><a href="admin.php?page=printify-sync-settings"><i class="fas fa-cog"></i> Settings</a></li>
                        </ul>
                    </nav>
                </div>
                
                <div class="header-right">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search logs...">
                    </div>
                    <div class="date-time">
                        <i class="far fa-clock"></i>
                        <span id="current-datetime">2025-03-03 11:10:17 UTC</span>
                    </div>
                    <div class="user-profile">
                        <span>Welcome, ApolloWeb</span>
                        <div class="avatar">AW</div>
                    </div>
                </div>
            </header>
            
            <div class="dashboard-content">
                <div class="page-header">
                    <h1>Log Viewer</h1>
                    <div class="page-actions">
                        <button class="btn-primary"><i class="fas fa-download"></i> Download Logs</button>
                        <button class="btn-danger"><i class="fas fa-trash"></i> Clear Logs</button>
                    </div>
                </div>
                
                <div class="logs-filters">
                    <div class="filter-group">
                        <label for="log-type">Log Type:</label>
                        <select id="log-type" class="form-select">
                            <option value="all">All Logs</option>
                            <option value="error">Errors</option>
                            <option value="warning">Warnings</option>
                            <option value="info">Info</option>
                            <option value="debug">Debug</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date-range">Date Range:</label>
                        <select id="date-range" class="form-select">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="week">Last 7 Days</option>
                            <option value="month">Last 30 Days</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="component">Component:</label>
                        <select id="component" class="form-select">
                            <option value="all">All Components</option>
                            <option value="products">Products</option>
                            <option value="orders">Orders</option>
                            <option value="api">API</option>
                            <option value="sync">Sync</option>
                        </select>
                    </div>
                    
                    <button class="btn-primary filter-btn"><i class="fas fa-filter"></i> Apply Filters</button>
                </div>
                
                <div class="logs-container">
                    <div class="logs-table-wrapper">
                        <table class="data-table logs-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Level</th>
                                    <th>Component</th>
                                    <th>Message</th>
                                    <th>Context</th>
                                </tr>
                