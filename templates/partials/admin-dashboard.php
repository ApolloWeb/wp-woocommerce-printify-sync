<?php
/**
 * Main admin dashboard for WooCommerce Printify Sync
 * Using Shards Dashboard theme
 *
 * @package WP_Woocommerce_Printify_Sync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current tab or default to 'dashboard'
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
$environment = get_option('wps_environment_mode', 'production');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Sidebar -->
        <aside class="main-sidebar col-12 col-md-3 col-lg-2 px-0">
            <div class="main-navbar">
                <nav class="navbar align-items-stretch navbar-light bg-white flex-md-nowrap border-bottom p-0">
                    <a class="navbar-brand w-100 mr-0" href="#" style="line-height: 25px;">
                        <div class="d-table m-auto">
                            <img id="main-logo" class="d-inline-block align-top mr-1" style="max-width: 25px;" 
                                src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__))) . 'images/logo.png'; ?>" alt="Logo">
                            <span class="d-none d-md-inline ml-1">Printify Sync</span>
                        </div>
                    </a>
                    <a class="toggle-sidebar d-sm-inline d-md-none d-lg-none">
                        <i class="material-icons">&#xE5C4;</i>
                    </a>
                </nav>
            </div>
            
            <div class="environment-indicator text-center py-2 <?php echo $environment === 'development' ? 'bg-warning' : 'bg-success'; ?>">
                <span class="text-white">
                    <i class="fas <?php echo $environment === 'development' ? 'fa-flask' : 'fa-globe'; ?>"></i>
                    <?php echo esc_html(ucfirst($environment)); ?> Mode
                </span>
            </div>
            
            <div class="nav-wrapper">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_tab === 'dashboard' ? 'active' : ''; ?>" 
                            href="?page=wp-woocommerce-printify-sync&tab=dashboard">
                            <i class="material-icons">dashboard</i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_tab === 'products' ? 'active' : ''; ?>" 
                            href="?page=wp-woocommerce-printify-sync&tab=products">
                            <i class="material-icons">inventory_2</i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_tab === 'orders' ? 'active' : ''; ?>" 
                            href="?page=wp-woocommerce-printify-sync&tab=orders">
                            <i class="material-icons">shopping_cart</i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_tab === 'tickets' ? 'active' : ''; ?>" 
                            href="?page=wp-woocommerce-printify-sync&tab=tickets">
                            <i class="material-icons">confirmation_number</i>
                            <span>Tickets</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_tab === 'logs' ? 'active' : ''; ?>" 
                            href="?page=wp-woocommerce-printify-sync&tab=logs">
                            <i class="material-icons">receipt_long</i>
                            <span>Logs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_tab === 'settings' ? 'active' : ''; ?>" 
                            href="?page=wp-woocommerce-printify-sync&tab=settings">
                            <i class="material-icons">settings</i>
                            <span>Settings</span>
                        </a>
                    </li>
                    
                    <?php if ($environment === 'development'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_tab === 'testing' ? 'active' : ''; ?>" 
                            href="?page=wp-woocommerce-printify-sync&tab=testing">
                            <i class="material-icons">science</i>
                            <span>API Testing</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>
        <!-- End Main Sidebar -->
        
        <main class="main-content col-lg-10 col-md-9 col-sm-12 p-0 offset-lg-2 offset-md-3">
            <div class="main-navbar sticky-top bg-white">
                <nav class="navbar align-items-stretch navbar-light flex-md-nowrap p-0">
                    <form action="#" class="main-navbar__search w-100 d-none d-md-flex d-lg-flex">
                        <div class="input-group input-group-seamless ml-3">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </div>
                            </div>
                            <input class="navbar-search form-control" type="text" placeholder="Search..." aria-label="Search">
                        </div>
                    </form>
                    
                    <ul class="navbar-nav border-left flex-row">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-nowrap px-3" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                                <span class="d-none d-md-inline-block">Quick Actions</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-sync text-success"></i> Sync Products
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-shopping-cart text-warning"></i> Check Orders
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cog text-info"></i> Settings
                                </a>
                            </div>
                        </li>
                    </ul>
                    
                    <nav class="nav">
                        <a href="#" class="nav-link nav-link-icon toggle-sidebar d-md-inline d-lg-none text-center" data-toggle="collapse" data-target=".header-navbar" aria-expanded="false" aria-controls="header-navbar">
                            <i class="material-icons">&#xE5D2;</i>
                        </a>
                    </nav>
                </nav>
            </div>
            
            <div class="main-content-container container-fluid px-4">
                <!-- Page Header -->
                <div class="page-header row no-gutters py-4">
                    <div class="col-12 col-sm-4 text-center text-sm-left mb-0">
                        <?php 
                        $page_title = 'Dashboard';
                        switch ($current_tab) {
                            case 'products':
                                $page_title = 'Products';
                                break;
                            case 'orders':
                                $page_title = 'Orders';
                                break;
                            case 'tickets':
                                $page_title = 'Tickets';
                                break;
                            case 'logs':
                                $page_title = 'Logs & Debugging';
                                break;
                            case 'settings':
                                $page_title = 'Settings';
                                break;
                            case 'testing':
                                $page_title = 'API Testing';
                                break;
                        }
                        ?>
                        <span class="text-uppercase page-subtitle">WooCommerce Printify Sync</span>
                        <h3 class="page-title"><?php echo esc_html($page_title); ?></h3>
                    </div>
                </div>
                <!-- End Page Header -->
                
                <?php
                // Load appropriate tab content
                switch ($current_tab) {
                    case 'dashboard':
                        include 'dashboard/main-dashboard.php';
                        break;
                    case 'settings':
                        include 'dashboard/settings-tab.php';
                        break;
                    case 'logs':
                        include 'dashboard/logs-tab.php';
                        break;
                    case 'products':
                        include 'dashboard/products-tab.php';
                        break;
                    case 'orders':
                        include 'dashboard/orders-tab.php';
                        break;
                    case 'tickets':
                        include 'dashboard/tickets-tab.php';
                        break;
                    case 'testing':
                        if ($environment === 'development') {
                            include 'dashboard/testing-tab.php';
                        } else {
                            echo '<div class="alert alert-danger">API Testing is only available in development mode.</div>';
                        }
                        break;
                    default:
                        include 'dashboard/main-dashboard.php';
                        break;
                }
                ?>
            </div>
            
            <footer class="main-footer d-flex p-2 px-3 bg-white border-top">
                <span class="copyright ml-auto my-auto mr-2">
                    <a href="https://github.com/ApolloWeb/wp-woocommerce-printify-sync" target="_blank">WooCommerce Printify Sync</a> &copy; <?php echo date('Y'); ?>
                </span>
            </footer>
        </main>
    </div>
</div>