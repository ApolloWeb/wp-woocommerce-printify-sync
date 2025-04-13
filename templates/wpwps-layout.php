<?php
/**
 * Main layout template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Set template
$this->set('template', basename($template_path, '.php'));
?>
<div class="wrap wpwps-container p-0">
    <!-- Full-width Enhanced Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark wpwps-navbar mb-4">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-dashboard')); ?>">
                <i class="fas fa-tshirt me-2"></i>
                <?php echo esc_html(__('Printify Sync', 'wp-woocommerce-printify-sync')); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#wpwpsMainNav" aria-controls="wpwpsMainNav" aria-expanded="false" aria-label="<?php esc_attr_e('Toggle navigation', 'wp-woocommerce-printify-sync'); ?>">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="wpwpsMainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $this->get('template') === 'wpwps-dashboard' ? 'active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-dashboard')); ?>">
                            <i class="fas fa-tachometer-alt"></i> <?php esc_html_e('Dashboard', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $this->get('template') === 'wpwps-products' ? 'active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-products')); ?>">
                            <i class="fas fa-box"></i> <?php esc_html_e('Products', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $this->get('template') === 'wpwps-orders' ? 'active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-orders')); ?>">
                            <i class="fas fa-shopping-cart"></i> <?php esc_html_e('Orders', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $this->get('template') === 'wpwps-shipping' ? 'active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-shipping')); ?>">
                            <i class="fas fa-truck"></i> <?php esc_html_e('Shipping', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $this->get('template') === 'wpwps-tickets' ? 'active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=wpwps-tickets')); ?>">
                            <i class="fas fa-ticket-alt"></i> <?php esc_html_e('Tickets', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex">
                    <button class="btn btn-outline-secondary me-2" id="refresh-data">
                        <i class="fas fa-sync-alt"></i> <?php esc_html_e('Refresh Data', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>" class="btn <?php echo $this->get('template') === 'wpwps-settings' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="fas fa-cog"></i> <?php esc_html_e('Settings', 'wp-woocommerce-printify-sync'); ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">
        <!-- Page Title -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="wpwps-title mb-0">
                    <?php echo esc_html($this->get('title', __('Printify Sync', 'wp-woocommerce-printify-sync'))); ?>
                </h1>
            </div>
        </div>
        
        <!-- Content -->
        <div class="row">
            <div class="col-md-12">
                <div class="wpwps-content">
                    <?php echo $this->getContent(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
