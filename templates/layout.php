<?php defined('ABSPATH') || exit; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($title ?? __('Printify Sync', 'wp-woocommerce-printify-sync')); ?></title>
    <?php do_action('admin_print_styles'); ?>
    <?php do_action('admin_print_scripts'); ?>
    <style>
        /* Custom CSS overrides for the dashboard */
        :root {
            --wpwps-primary: #96588a;
            --wpwps-secondary: #0077b6;
            --wpwps-accent: #00b4d8;
            --wpwps-dark: #0f1a20;
            --wpwps-light: #ffffff;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Inter', sans-serif;
        }
        
        .navbar-wpwps {
            background-color: var(--wpwps-primary);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card-wpwps {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .card-wpwps:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background-color: var(--wpwps-primary);
            border-color: var(--wpwps-primary);
        }
        
        .btn-primary:hover {
            background-color: #7d4675;
            border-color: #7d4675;
        }
    </style>
</head>
<body>
    <div class="wrapper d-flex flex-column min-vh-100">
        <nav class="navbar navbar-expand-lg navbar-dark navbar-wpwps">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <i class="fa-solid fa-print me-2"></i>
                    <?php echo esc_html__('Printify Sync', 'wp-woocommerce-printify-sync'); ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link<?php echo $active_page === 'dashboard' ? ' active' : ''; ?>" href="?page=wpwps-dashboard">
                                <i class="fa-solid fa-gauge-high me-1"></i> <?php echo esc_html__('Dashboard', 'wp-woocommerce-printify-sync'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $active_page === 'products' ? ' active' : ''; ?>" href="?page=wpwps-products">
                                <i class="fa-solid fa-shirt me-1"></i> <?php echo esc_html__('Products', 'wp-woocommerce-printify-sync'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $active_page === 'orders' ? ' active' : ''; ?>" href="?page=wpwps-orders">
                                <i class="fa-solid fa-cart-shopping me-1"></i> <?php echo esc_html__('Orders', 'wp-woocommerce-printify-sync'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $active_page === 'settings' ? ' active' : ''; ?>" href="?page=wpwps-settings">
                                <i class="fa-solid fa-gear me-1"></i> <?php echo esc_html__('Settings', 'wp-woocommerce-printify-sync'); ?>
                            </a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center">
                        <div class="search-container me-3">
                            <input type="search" class="form-control form-control-sm" placeholder="<?php echo esc_attr__('Search...', 'wp-woocommerce-printify-sync'); ?>">
                        </div>
                        <div class="dropdown">
                            <a class="nav-link text-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-user-circle me-1"></i>
                                <span class="d-none d-md-inline"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo esc_url(get_edit_profile_url()); ?>"><i class="fa-solid fa-user me-2"></i><?php echo esc_html__('Profile', 'wp-woocommerce-printify-sync'); ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo esc_url(wp_logout_url()); ?>"><i class="fa-solid fa-right-from-bracket me-2"></i><?php echo esc_html__('Logout', 'wp-woocommerce-printify-sync'); ?></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        
        <div class="container-fluid py-4 flex-grow-1">
            <!-- Toast container -->
            <div class="toast-container position-fixed top-0 end-0 p-3">
                <!-- Toasts will be injected here via JavaScript -->
            </div>
            
            <!-- Page content -->
            <?php echo $content ?? ''; ?>
        </div>
        
        <footer class="mt-auto py-3 bg-light">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> WP WooCommerce Printify Sync</p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <p class="mb-0 text-muted">Version: <?php echo esc_html(WPWPS_VERSION); ?></p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <?php do_action('admin_footer'); ?>
    <?php do_action('admin_print_footer_scripts'); ?>
</body>
</html>
