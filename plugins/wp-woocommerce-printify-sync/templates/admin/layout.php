<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($title ?? 'Printify Sync'); ?></title>
    <?php wp_head(); ?>
</head>
<body class="wpwps-admin">
    <div class="wpwps-wrapper">
        <!-- Sidebar -->
        <nav class="wpwps-sidebar">
            <div class="sidebar-header">
                <img src="<?php echo esc_url(WPWPS_PLUGIN_URL . 'assets/images/logo.svg'); ?>" alt="Printify Sync">
            </div>
            
            <ul class="sidebar-nav">
                <li class="nav-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=wpwps-dashboard'); ?>" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item <?php echo $current_page === 'products' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=wpwps-products'); ?>" class="nav-link">
                        <i class="fas fa-boxes"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="nav-item <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=wpwps-settings'); ?>" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="nav-item <?php echo $current_page === 'logs' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=wpwps-logs'); ?>" class="nav-link">
                        <i class="fas fa-list"></i>
                        <span>Logs</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="wpwps-main">
            <!-- Top Navigation -->
            <nav class="wpwps-topnav">
                <div class="d-flex justify-content-between align-items-center">
                    <button class="btn btn-link sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="topnav-right">
                        <div class="sync-status" id="sync-status">
                            <i class="fas fa-sync-alt"></i>
                            <span>Last sync: 5 minutes ago</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#">Profile</a></li>
                                <li><a class="dropdown-item" href="#">Help</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">
                        <h1 class="content-title"><?php echo esc_html($title ?? ''); ?></h1>
                    </div>
                </div>

                <div class="content">
                    <div class="container-fluid">
                        <?php echo $content ?? ''; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php wp_footer(); ?>
</body>
</html>