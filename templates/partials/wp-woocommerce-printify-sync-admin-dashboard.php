<?php
/**
 * Main admin dashboard using Tabler UI
 *
 * @package    WP_Woocommerce_Printify_Sync
 * @subpackage WP_Woocommerce_Printify_Sync/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="page">
    <!-- Top navigation header -->
    <header class="navbar navbar-expand-md navbar-light d-print-none">
        <div class="container-xl">
            <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                <img src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__))) . 'images/logo.png'; ?>" width="110" height="32" alt="WooCommerce Printify Sync" class="navbar-brand-image">
            </h1>
            
            <div class="navbar-nav flex-row order-md-last">
                <!-- Environment badge -->
                <?php $environment = get_option('wps_environment_mode', 'production'); ?>
                <div class="nav-item me-3">
                    <span class="badge bg-<?php echo $environment === 'production' ? 'success' : 'warning'; ?>">
                        <?php echo esc_html(ucfirst($environment)); ?>
                    </span>
                </div>
                
                <!-- Notification dropdown -->
                <div class="nav-item dropdown d-none d-md-flex me-3">
                    <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" /><path d="M9 17v1a3 3 0 0 0 6 0v-1" /></svg>
                        <?php $alert_count = 0; // Replace with actual function to get notifications count ?>
                        <?php if ($alert_count > 0): ?>
                        <span class="badge bg-red"><?php echo esc_html($alert_count); ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-card">
                        <div class="card">
                            <div class="card-body">
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusamus ad amet consectetur.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="navbar-expand-md">
        <div class="collapse navbar-collapse" id="navbar-menu">
            <div class="navbar navbar-light">
                <div class="container-xl">
                    <ul class="navbar-nav">
                        <li class="nav-item <?php echo !isset($_GET['tab']) || $_GET['tab'] === 'dashboard' ? 'active' : ''; ?>">
                            <a class="nav-link" href="?page=<?php echo esc_attr($this->plugin_name); ?>">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
                                </span>
                                <span class="nav-link-title">
                                    Dashboard
                                </span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo isset($_GET['tab']) && $_GET['tab'] === 'settings' ? 'active' : ''; ?>">
                            <a class="nav-link" href="?page=<?php echo esc_attr($this->plugin_name); ?>&tab=settings">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" /><circle cx="12" cy="12" r="3" /></svg>
                                </span>
                                <span class="nav-link-title">
                                    Settings
                                </span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo isset($_GET['tab']) && $_GET['tab'] === 'logs' ? 'active' : ''; ?>">
                            <a class="nav-link" href="?page=<?php echo esc_attr($this->plugin_name); ?>&tab=logs">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" /><rect x="9" y="3" width="6" height="4" rx="2" /><line x1="9" y1="12" x2="9.01" y2="12" /><line x1="13" y1="12" x2="15" y2="12" /><line x1="9" y1="16" x2="9.01" y2="16" /><line x1="13" y1="16" x2="15" y2="16" /></svg>
                                </span>
                                <span class="nav-link-title">
                                    Logs & Debugging
                                </span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo isset($_GET['tab']) && $_GET['tab'] === 'products' ? 'active' : ''; ?>">
                            <a class="nav-link" href="?page=<?php echo esc_attr($this->plugin_name); ?>&tab=products">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9l8 -4.5" /><line x1="12" y1="12" x2="12" y2="21" /><line x1="12" y1="12" x2="4" y2="7.5" /><line x1="12" y1="12" x2="20" y2="7.5" /></svg>
                                </span>
                                <span class="nav-link-title">
                                    Products
                                </span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo isset($_GET['tab']) && $_GET['tab'] === 'tickets' ? 'active' : ''; ?>">
                            <a class="nav-link" href="?page=<?php echo esc_attr($this->plugin_name); ?>&tab=tickets">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="15" y1="5" x2="15" y2="7" /><line x1="15" y1="11" x2="15" y2="13" /><line x1="15" y1="17" x2="15" y2="19" /><path d="M5 5h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-3a2 2 0 0 0 0 -4v-3a2 2 0 0 1 2 -2" /></svg>
                                </span>
                                <span class="nav-link-title">
                                    Ticketing
                                </span>
                            </a>
                        </li>
                        <?php if ($environment === 'development'): ?>
                        <li class="nav-item <?php echo isset($_GET['tab']) && $_GET['tab'] === 'testing' ? 'active' : ''; ?>">
                            <a class="nav-link" href="?page=<?php echo esc_attr($this->plugin_name); ?>&tab=testing">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 3v3h4v-3z" /><path d="M3 8h18l-3 7h-12z" /><path d="M7 15h10v4h-10z" /><path d="M8 12v-4h8v4" /></svg>
                                </span>
                                <span class="nav-link-title">
                                    API Testing
                                </span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-wrapper">
        <div class="container-xl">
            <!-- Page title -->
            <div class="page-header d-print-none">
                <div class="row align-items-center">
                    <div class="col">
                        <?php
                        // Get the current tab title
                        $tab_title = 'Dashboard';
                        if (isset($_GET['tab'])) {
                            switch ($_GET['tab']) {
                                case 'settings':
                                    $tab_title = 'Settings';
                                    break;
                                case 'logs':
                                    $tab_title = 'Logs & Debugging';
                                    break;
                                case 'products':
                                    $tab_title = 'Products';
                                    break;
                                case 'tickets':
                                    $tab_title = 'Ticketing';
                                    break;
                                case 'testing':
                                    $tab_title = 'API Testing';
                                    break;
                            }
                        }
                        ?>
                        <h2 class="page-title">
                            <?php echo esc_html($tab_title); ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="page-body">
            <div class="container-xl">
                <?php
                // Load appropriate tab content based on URL parameter
                $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
                
                switch ($current_tab) {
                    case 'settings':
                        include_once('dashboard/settings-tab.php');
                        break;
                    case 'logs':
                        include_once('dashboard/logs-tab.php');
                        break;
                    case 'products':
                        include_once('dashboard/products-tab.php');
                        break;
                    case 'tickets':
                        include_once('dashboard/tickets-tab.php');
                        break;
                    case 'testing':
                        if ($environment === 'development') {
                            include_once('dashboard/testing-tab.php');
                        } else {
                            echo '<div class="alert alert-danger">API Testing is only available in development mode.</div>';
                        }
                        break;
                    default:
                        include_once('dashboard/main-dashboard.php');
                        break;
                }
                ?>
            </div>
        </div>
        
        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row text-center align-items-center flex-row-reverse">
                    <div class="col-lg-auto ms-lg-auto">
                        <ul class="list-inline list-inline-dots mb-0">
                            <li class="list-inline-item"><a href="https://github.com/ApolloWeb/wp-woocommerce-printify-sync" target="_blank" class="link-secondary">Documentation</a></li>
                            <li class="list-inline-item"><a href="https://github.com/ApolloWeb/wp-woocommerce-printify-sync/issues" target="_blank" class="link-secondary">Support</a></li>
                        