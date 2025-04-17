<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Base class for admin pages
 * 
 * Provides common functionality and styling for admin pages
 */
abstract class AdminPage {
    /**
     * Page title
     *
     * @var string
     */
    protected $page_title;
    
    /**
     * Menu title
     *
     * @var string
     */
    protected $menu_title;
    
    /**
     * Capability required
     *
     * @var string
     */
    protected $capability = 'manage_options';
    
    /**
     * Menu slug
     *
     * @var string
     */
    protected $menu_slug;
    
    /**
     * Parent slug
     *
     * @var string
     */
    protected $parent_slug = '';
    
    /**
     * Icon
     *
     * @var string
     */
    protected $icon = '';
    
    /**
     * Position
     *
     * @var int|null
     */
    protected $position = null;

    /**
     * Initialize the admin page
     */
    public function init() {
        add_action('admin_menu', [$this, 'register_menu']);
    }
    
    /**
     * Register the menu page
     */
    public function register_menu() {
        if ($this->parent_slug) {
            add_submenu_page(
                $this->parent_slug,
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->menu_slug,
                [$this, 'render_page']
            );
        } else {
            add_menu_page(
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->menu_slug,
                [$this, 'render_page'],
                $this->icon,
                $this->position
            );
        }
    }
    
    /**
     * Render the page header
     */
    protected function render_header() {
        ?>
        <nav class="navbar navbar-expand-lg sticky-top navbar-light bg-white wpwps-admin-navbar shadow-sm mb-4">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="<?php echo admin_url('admin.php?page=' . $this->menu_slug); ?>">
                    <i class="fas fa-tshirt me-2 text-primary"></i>
                    <span class="fw-bold"><?php echo esc_html($this->page_title); ?></span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#wpwpsTopNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="wpwpsTopNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link<?php echo $this->menu_slug === 'wpwps-dashboard' ? ' active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpwps-dashboard'); ?>">
                                <i class="fas fa-chart-line me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $this->menu_slug === 'wpwps-products' ? ' active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpwps-products'); ?>">
                                <i class="fas fa-box me-1"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $this->menu_slug === 'wpwps-orders' ? ' active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpwps-orders'); ?>">
                                <i class="fas fa-shopping-cart me-1"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $this->menu_slug === 'wpwps-settings' ? ' active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpwps-settings'); ?>">
                                <i class="fas fa-cogs me-1"></i> Settings
                            </a>
                        </li>
                    </ul>
                    
                    <div class="d-flex">
                        <div class="wpwps-admin-search me-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control border-start-0" placeholder="Search..." aria-label="Search">
                            </div>
                        </div>
                        
                        <div class="wpwps-admin-notifications me-3">
                            <div class="dropdown">
                                <a href="#" class="wpwps-admin-notification-icon btn btn-light position-relative" data-bs-toggle="dropdown">
                                    <i class="fas fa-bell"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 p-0" style="width: 320px;">
                                    <div class="p-2 border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold">Notifications</h6>
                                        <a href="#" class="text-decoration-none small">Mark all as read</a>
                                    </div>
                                    <div class="notification-list">
                                        <a href="#" class="dropdown-item p-3 border-bottom d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="bg-primary bg-opacity-10 p-2 rounded">
                                                    <i class="fas fa-sync-alt text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <p class="mb-0">Product sync completed</p>
                                                <p class="small text-muted mb-0">24 products updated</p>
                                                <p class="small text-muted mb-0">2 minutes ago</p>
                                            </div>
                                        </a>
                                        <a href="#" class="dropdown-item p-3 border-bottom d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="bg-success bg-opacity-10 p-2 rounded">
                                                    <i class="fas fa-check text-success"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <p class="mb-0">Order #1234 processed</p>
                                                <p class="small text-muted mb-0">Order was sent to Printify</p>
                                                <p class="small text-muted mb-0">30 minutes ago</p>
                                            </div>
                                        </a>
                                        <a href="#" class="dropdown-item p-3 d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="bg-danger bg-opacity-10 p-2 rounded">
                                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <p class="mb-0">API error detected</p>
                                                <p class="small text-muted mb-0">Connection timeout</p>
                                                <p class="small text-muted mb-0">1 hour ago</p>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="p-2 border-top text-center">
                                        <a href="#" class="text-decoration-none small">View all notifications</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="wpwps-admin-user">
                            <div class="dropdown">
                                <a class="dropdown-toggle d-flex align-items-center text-decoration-none" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php echo get_avatar(get_current_user_id(), 32, '', '', ['class' => 'rounded-circle']); ?>
                                    <div class="ms-2 d-none d-sm-block">
                                        <div class="wpwps-admin-username fw-bold">
                                            <?php echo esc_html(wp_get_current_user()->display_name); ?>
                                        </div>
                                        <div class="wpwps-admin-role small text-muted">
                                            <?php echo esc_html($this->get_user_role()); ?>
                                        </div>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userDropdown">
                                    <li class="dropdown-header">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                                            <span class="text-muted"><?php echo esc_html(wp_get_current_user()->user_email); ?></span>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo esc_url(get_edit_profile_url()); ?>">
                                        <i class="fas fa-user me-2"></i> My Profile
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>">
                                        <i class="fas fa-cogs me-2"></i> Settings
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo esc_url(wp_logout_url()); ?>">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <div id="wpwps-toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11000;"></div>
        <?php
    }
    
    /**
     * Get the user role
     *
     * @return string
     */
    protected function get_user_role() {
        $user = wp_get_current_user();
        if (empty($user->roles)) {
            return 'No Role';
        }
        
        $wp_roles = wp_roles();
        $role = $user->roles[0];
        return isset($wp_roles->role_names[$role]) ? $wp_roles->role_names[$role] : $role;
    }
    
    /**
     * Render the page wrapper
     */
    protected function render_wrapper_start() {
        ?>
        <div class="wrap wpwps-admin-wrap">
            <?php $this->render_header(); ?>
            <div class="wpwps-admin-content">
                <div class="container-fluid px-0">
        <?php
    }
    
    /**
     * Render the page wrapper end
     */
    protected function render_wrapper_end() {
        ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the page
     */
    public function render_page() {
        $this->render_wrapper_start();
        $this->render_content();
        $this->render_wrapper_end();
    }
    
    /**
     * Render the page content
     */
    abstract protected function render_content();
}
