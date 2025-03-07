<?php
/**
 * Main admin dashboard for WooCommerce Printify Sync
 *
 * @package WP_Woocommerce_Printify_Sync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current tab or default to 'dashboard'
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

// Get environment setting
$environment = get_option('wps_environment_mode', 'production');
?>

<div class="wps-admin-container">
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light wps-top-nav">
        <div class="container-fluid">
            <a class="wps-nav-brand" href="?page=wp-woocommerce-printify-sync">
                <img src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__))) . 'images/logo.png'; ?>" alt="Printify Sync">
                <span>Printify Sync</span>
            </a>
            
            <span class="wps-env-badge wps-env-<?php echo esc_attr($environment); ?> me-3">
                <i class="fas <?php echo $environment === 'production' ? 'fa-globe' : 'fa-flask'; ?>"></i>
                <?php echo esc_html(ucfirst($environment)); ?>
            </span>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#wpsNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="wpsNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item wps-nav-item">
                        <a class="nav-link <?php echo $current_tab === 'dashboard' ? 'active' : ''; ?>" href="?page=wp-woocommerce-printify-sync&tab=dashboard">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item wps-nav-item">
                        <a class="nav-link <?php echo $current_tab === 'products' ? 'active' : ''; ?>" href="?page=wp-woocommerce-printify-sync&tab=products">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li class="nav-item wps-nav-item">
                        <a class="nav-link <?php echo $current_tab === 'orders' ? 'active' : ''; ?>" href="?page=wp-woocommerce-printify-sync&tab=orders">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li class="nav-item wps-nav-item">
                        <a class="nav-link <?php echo $current_tab === 'tickets' ? 'active' : ''; ?>" href="?page=wp-woocommerce-printify-sync&tab=tickets">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Tickets</span>
                        </a>
                    </li>
                    <li class="nav-item wps-nav-item">
                        <a class="nav-link <?php echo $current_tab === 'logs' ? 'active' : ''; ?>" href="?page=wp-woocommerce-printify-sync&tab=logs">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Logs</span>
                        </a>
                    </li>
                    <li class="nav-item wps-nav-item">
                        <a class="nav-link <?php echo $current_tab === 'settings' ? 'active' : ''; ?>" href="?page=wp-woocommerce-printify-sync&tab=settings">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Content Container -->
    <div class="wps-content">
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
            default:
                include 'dashboard/main-dashboard.php';
                break;
        }
        ?>
    </div>
</div>