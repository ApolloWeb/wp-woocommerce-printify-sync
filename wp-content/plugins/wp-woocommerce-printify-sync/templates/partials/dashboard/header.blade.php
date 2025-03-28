<header class="wpwps-header">
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <span class="navbar-brand me-3">
                    <img src="{{ WPWPS_URL }}assets/images/printify-logo.png" alt="WooCommerce Printify Sync" height="30">
                </span>
                <h1 class="text-primary fw-bold mb-0">{{ __('Printify Sync', 'wp-woocommerce-printify-sync') }}</h1>
            </div>
            
            <div class="wpwps-navbar-actions d-flex align-items-center">
                <div class="position-relative me-4">
                    <input type="text" class="form-control wpwps-search-box" placeholder="{{ __('Search...', 'wp-woocommerce-printify-sync') }}">
                    <i class="fas fa-search wpwps-search-icon"></i>
                </div>
                
                <div class="dropdown me-4">
                    <a href="#" class="position-relative wpwps-notification-bell" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="wpwps-notification-badge">3</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end wpwps-dropdown-menu p-0" style="width: 320px;">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">{{ __('Notifications', 'wp-woocommerce-printify-sync') }}</h6>
                            <a href="#" class="text-muted text-decoration-none small">
                                {{ __('Mark all as read', 'wp-woocommerce-printify-sync') }}
                            </a>
                        </div>
                        <div class="wpwps-notifications-list">
                            <a href="#" class="dropdown-item p-3 border-bottom d-flex unread">
                                <div class="me-3">
                                    <div class="bg-primary text-white wpwps-notification-icon">
                                        <i class="fas fa-sync-alt"></i>
                                    </div>
                                </div>
                                <div>
                                    <p class="mb-0 fw-medium">{{ __('Product sync completed', 'wp-woocommerce-printify-sync') }}</p>
                                    <span class="text-muted small">{{ __('15 products successfully synced', 'wp-woocommerce-printify-sync') }}</span>
                                    <p class="small text-muted mb-0 mt-1">{{ __('10 minutes ago', 'wp-woocommerce-printify-sync') }}</p>
                                </div>
                            </a>
                            <a href="#" class="dropdown-item p-3 border-bottom d-flex unread">
                                <div class="me-3">
                                    <div class="bg-success text-white wpwps-notification-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                                <div>
                                    <p class="mb-0 fw-medium">{{ __('New order received', 'wp-woocommerce-printify-sync') }}</p>
                                    <span class="text-muted small">{{ __('Order #5789 is ready for processing', 'wp-woocommerce-printify-sync') }}</span>
                                    <p class="small text-muted mb-0 mt-1">{{ __('1 hour ago', 'wp-woocommerce-printify-sync') }}</p>
                                </div>
                            </a>
                            <a href="#" class="dropdown-item p-3 border-bottom d-flex">
                                <div class="me-3">
                                    <div class="bg-warning text-white wpwps-notification-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                </div>
                                <div>
                                    <p class="mb-0 fw-medium">{{ __('API rate limit warning', 'wp-woocommerce-printify-sync') }}</p>
                                    <span class="text-muted small">{{ __('75% of your daily quota has been used', 'wp-woocommerce-printify-sync') }}</span>
                                    <p class="small text-muted mb-0 mt-1">{{ __('3 hours ago', 'wp-woocommerce-printify-sync') }}</p>
                                </div>
                            </a>
                        </div>
                        <div class="p-3">
                            <a href="#" class="btn btn-light btn-sm d-block text-center">
                                {{ __('View all notifications', 'wp-woocommerce-printify-sync') }}
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle wpwps-user-menu" data-bs-toggle="dropdown">
                        <div class="wpwps-avatar me-2">
                            <?php
                            $current_user = wp_get_current_user();
                            echo get_avatar($current_user->ID, 38, '', $current_user->display_name, ['class' => 'rounded-circle']);
                            ?>
                        </div>
                        <div>
                            <span class="d-block fw-medium"><?php echo esc_html($current_user->display_name); ?></span>
                            <small class="text-muted">
                                <?php 
                                if (current_user_can('manage_options')) {
                                    echo __('Administrator', 'wp-woocommerce-printify-sync');
                                } elseif (current_user_can('manage_woocommerce')) {
                                    echo __('Shop Manager', 'wp-woocommerce-printify-sync');
                                } else {
                                    echo __('User', 'wp-woocommerce-printify-sync');
                                }
                                ?>
                            </small>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end wpwps-dropdown-menu">
                        <h6 class="dropdown-header fw-medium">{{ __('Welcome', 'wp-woocommerce-printify-sync') }}!</h6>
                        <a href="<?php echo admin_url('profile.php'); ?>" class="dropdown-item">
                            <i class="fas fa-user me-2"></i> {{ __('My Profile', 'wp-woocommerce-printify-sync') }}
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=wpwps-settings'); ?>" class="dropdown-item">
                            <i class="fas fa-cog me-2"></i> {{ __('Settings', 'wp-woocommerce-printify-sync') }}
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=wpwps-help'); ?>" class="dropdown-item">
                            <i class="fas fa-question-circle me-2"></i> {{ __('Help Center', 'wp-woocommerce-printify-sync') }}
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo wp_logout_url(admin_url()); ?>" class="dropdown-item">
                            <i class="fas fa-sign-out-alt me-2"></i> {{ __('Logout', 'wp-woocommerce-printify-sync') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- Secondary navbar with tabs -->
<div class="wpwps-secondary-navbar">
    <div class="container-fluid">
        <ul class="nav nav-tabs" id="wpwpsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ !isset($_GET['page']) || $_GET['page'] == 'printify-sync' ? 'active' : '' }}" 
                   href="<?php echo admin_url('admin.php?page=printify-sync'); ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> {{ __('Dashboard', 'wp-woocommerce-printify-sync') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ isset($_GET['page']) && $_GET['page'] == 'printify-sync-products' ? 'active' : '' }}" 
                   href="<?php echo admin_url('admin.php?page=printify-sync-products'); ?>">
                    <i class="fas fa-box-open me-2"></i> {{ __('Products', 'wp-woocommerce-printify-sync') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ isset($_GET['page']) && $_GET['page'] == 'printify-sync-orders' ? 'active' : '' }}" 
                   href="<?php echo admin_url('admin.php?page=printify-sync-orders'); ?>">
                    <i class="fas fa-shopping-cart me-2"></i> {{ __('Orders', 'wp-woocommerce-printify-sync') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ isset($_GET['page']) && $_GET['page'] == 'printify-sync-blueprints' ? 'active' : '' }}" 
                   href="<?php echo admin_url('admin.php?page=printify-sync-blueprints'); ?>">
                    <i class="fas fa-paint-brush me-2"></i> {{ __('Blueprints', 'wp-woocommerce-printify-sync') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ isset($_GET['page']) && $_GET['page'] == 'printify-sync-settings' ? 'active' : '' }}" 
                   href="<?php echo admin_url('admin.php?page=printify-sync-settings'); ?>">
                    <i class="fas fa-cog me-2"></i> {{ __('Settings', 'wp-woocommerce-printify-sync') }}
                </a>
            </li>
        </ul>
    </div>
</div>