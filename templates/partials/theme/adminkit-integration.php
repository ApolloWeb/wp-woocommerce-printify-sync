<?php
/**
 * AdminKit integration for WooCommerce Printify Sync
 *
 * @package    WP_Woocommerce_Printify_Sync
 * @subpackage WP_Woocommerce_Printify_Sync/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrapper">
    <div class="main">
        <nav class="navbar navbar-expand navbar-light navbar-bg">
            <a class="sidebar-toggle js-sidebar-toggle d-none">
                <i class="hamburger align-self-center"></i>
            </a>
            <div class="navbar-brand">
                <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/images/logo.png'); ?>" height="32" alt="WooCommerce Printify Sync">
                <span>WooCommerce Printify Sync</span>
            </div>

            <div class="navbar-collapse collapse">
                <ul class="navbar-nav navbar-align">
                    <li class="nav-item dropdown">
                        <a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
                            <div class="position-relative">
                                <i class="align-middle" data-feather="bell"></i>
                                <?php $alert_count = WPS_Notifications::get_unread_count(); ?>
                                <?php if ($alert_count > 0): ?>
                                <span class="indicator"><?php echo esc_html($alert_count); ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
                            <div class="dropdown-menu-header">
                                <?php echo esc_html($alert_count); ?> New Notifications
                            </div>
                            <div class="list-group">
                                <?php $notifications = WPS_Notifications::get_recent(5); ?>
                                <?php if (!empty($notifications)): ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <a href="<?php echo esc_url($notification['link']); ?>" class="list-group-item">
                                            <div class="row g-0 align-items-center">
                                                <div class="col-2">
                                                    <i class="text-<?php echo esc_attr($notification['type']); ?>" data-feather="<?php echo esc_attr($notification['icon']); ?>"></i>
                                                </div>
                                                <div class="col-10">
                                                    <div class="text-dark"><?php echo esc_html($notification['title']); ?></div>
                                                    <div class="text-muted small mt-1"><?php echo esc_html($notification['message']); ?></div>
                                                    <div class="text-muted small mt-1"><?php echo esc_html(human_time_diff(strtotime($notification['created_at']), current_time('timestamp'))); ?> ago</div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="list-group-item">
                                        <div class="text-muted">No new notifications</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="dropdown-menu-footer">
                                <a href="?page=wps-dashboard&tab=notifications" class="text-muted">Show all notifications</a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="content">
            <div class="container-fluid p-0">
                <!-- Your page content will go here -->
                <div class="wps-admin-content">
                    <?php
                    // Load appropriate tab content
                    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
                    switch ($current_tab) {
                        case 'settings':
                            include 'dashboard/settings-tab.php';
                            break;
                        case 'logs':
                            include 'dashboard/logs-tab.php';
                            break;
                        case 'products':
                            include 'dashboard/products-tab.php';
                            break;
                        case 'shipping':
                            include 'dashboard/shipping-tab.php';
                            break;
                        case 'tickets':
                            include 'dashboard/tickets-tab.php';
                            break;
                        case 'testing':
                            include 'dashboard/testing-tab.php';
                            break;
                        default:
                            include 'dashboard/main-dashboard.php';
                            break;
                    }
                    ?>
                </div>
            </div>
        </main>

        <footer class="footer">
            <div class="container-fluid">
                <div class="row text-muted">
                    <div class="col-6 text-start">
                        <p class="mb-0">
                            <a href="https://github.com/ApolloWeb/wp-woocommerce-printify-sync" class="text-muted" target="_blank"><strong>WooCommerce Printify Sync</strong></a> &copy; <?php echo date('Y'); ?>
                        </p>
                    </div>
                    <div class="col-6 text-end">
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <a class="text-muted" href="https://github.com/ApolloWeb/wp-woocommerce-printify-sync/issues" target="_blank">Support</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>