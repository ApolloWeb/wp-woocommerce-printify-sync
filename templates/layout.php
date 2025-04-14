<?php
/**
 * Main layout template for admin pages
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(get_admin_page_title()); ?></title>
    <?php do_action('admin_print_styles'); ?>
    <?php do_action('admin_print_scripts'); ?>
</head>
<body class="wpwps-admin">
    <div class="wpwps-container">
        <!-- Top Navigation -->
        <nav class="wpwps-navbar">
            <div class="wpwps-navbar-brand-wrapper">
                <a class="wpwps-navbar-brand" href="#">
                    <span class="wpwps-logo-icon"><i class="fas fa-tshirt"></i></span>
                    <span class="wpwps-logo-text">Printify Sync</span>
                </a>
                <button class="wpwps-navbar-toggler" id="navbar-toggler">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="wpwps-navbar-menu-wrapper">
                <ul class="wpwps-nav-tabs">
                    <li class="wpwps-nav-item<?php echo esc_attr($current_page === 'dashboard' ? ' active' : ''); ?>">
                        <a class="wpwps-nav-link" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-dashboard')); ?>">
                            <i class="fas fa-home"></i>
                            <span class="menu-title">Dashboard</span>
                        </a>
                    </li>
                    <li class="wpwps-nav-item<?php echo esc_attr($current_page === 'products' ? ' active' : ''); ?>">
                        <a class="wpwps-nav-link" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-products')); ?>">
                            <i class="fas fa-box"></i>
                            <span class="menu-title">Products</span>
                        </a>
                    </li>
                    <li class="wpwps-nav-item<?php echo esc_attr($current_page === 'orders' ? ' active' : ''); ?>">
                        <a class="wpwps-nav-link" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-orders')); ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="menu-title">Orders</span>
                        </a>
                    </li>
                    <li class="wpwps-nav-item<?php echo esc_attr($current_page === 'shipping' ? ' active' : ''); ?>">
                        <a class="wpwps-nav-link" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-shipping')); ?>">
                            <i class="fas fa-truck"></i>
                            <span class="menu-title">Shipping</span>
                        </a>
                    </li>
                    <li class="wpwps-nav-item<?php echo esc_attr($current_page === 'tickets' ? ' active' : ''); ?>">
                        <a class="wpwps-nav-link" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-tickets')); ?>">
                            <i class="fas fa-ticket-alt"></i>
                            <span class="menu-title">Tickets</span>
                        </a>
                    </li>
                    <li class="wpwps-nav-item<?php echo esc_attr($current_page === 'settings' ? ' active' : ''); ?>">
                        <a class="wpwps-nav-link" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>">
                            <i class="fas fa-cog"></i>
                            <span class="menu-title">Settings</span>
                        </a>
                    </li>
                </ul>
                <div class="wpwps-navbar-right">
                    <div class="wpwps-sync-status">
                        <i class="fas fa-sync-alt"></i>
                        <span>Last sync: <?php echo esc_html(get_option('wpwps_last_sync', 'Never')); ?></span>
                    </div>
                    
                    <!-- Role Dropdown -->
                    <div class="wpwps-dropdown">
                        <button class="wpwps-dropdown-toggle" id="role-dropdown-toggle">
                            <span class="wpwps-role-badge">Admin</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="wpwps-dropdown-menu" id="role-dropdown-menu">
                            <a class="wpwps-dropdown-item active" href="#">
                                <i class="fas fa-user-shield"></i> Admin
                            </a>
                            <a class="wpwps-dropdown-item" href="#">
                                <i class="fas fa-user-edit"></i> Editor
                            </a>
                            <a class="wpwps-dropdown-item" href="#">
                                <i class="fas fa-user"></i> Viewer
                            </a>
                        </div>
                    </div>
                    
                    <!-- User Profile Dropdown -->
                    <div class="wpwps-dropdown">
                        <button class="wpwps-dropdown-toggle wpwps-profile-toggle" id="profile-dropdown-toggle">
                            <?php 
                            $current_user = wp_get_current_user();
                            $display_name = $current_user->display_name;
                            $user_email = $current_user->user_email;
                            $avatar_url = get_avatar_url($current_user->ID, ['size' => 40]);
                            ?>
                            <div class="wpwps-avatar">
                                <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>">
                            </div>
                            <span class="wpwps-user-name d-none d-md-inline-block"><?php echo esc_html($display_name); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="wpwps-dropdown-menu wpwps-profile-menu" id="profile-dropdown-menu">
                            <div class="wpwps-dropdown-header">
                                <div class="wpwps-user-info">
                                    <h6><?php echo esc_html($display_name); ?></h6>
                                    <p><?php echo esc_html($user_email); ?></p>
                                </div>
                            </div>
                            <div class="wpwps-dropdown-divider"></div>
                            <a class="wpwps-dropdown-item" href="<?php echo esc_url(admin_url('profile.php')); ?>">
                                <i class="fas fa-user-circle"></i> My Profile
                            </a>
                            <a class="wpwps-dropdown-item" href="#">
                                <i class="fas fa-cog"></i> Account Settings
                            </a>
                            <div class="wpwps-dropdown-divider"></div>
                            <a class="wpwps-dropdown-item" href="<?php echo esc_url(wp_logout_url(admin_url())); ?>">
                                <i class="fas fa-sign-out-alt"></i> Log Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <div class="wpwps-content-wrapper">
            <div class="wpwps-page-heading">
                <h1><?php echo esc_html($page_title ?? get_admin_page_title()); ?></h1>
            </div>
            <div class="wpwps-content">
                <?php
                // Page content will be included here
                if (isset($content_template) && file_exists($content_template)) {
                    include $content_template;
                }
                ?>
            </div>
            <footer class="wpwps-footer">
                <div class="wpwps-footer-inner">
                    <p>&copy; <?php echo esc_html(date('Y')); ?> WP WooCommerce Printify Sync</p>
                </div>
            </footer>
        </div>
    </div>
    <?php do_action('admin_footer'); ?>
</body>
</html>
