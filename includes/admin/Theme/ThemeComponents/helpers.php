<?php
/**
 * Helper functions for the admin theme components
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents
 * @version 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get sidebar menu items
 *
 * @return string Sidebar menu items HTML
 */
function get_sidebar_menu_items() {
    ob_start();
    ?>
    <li class="nav-item">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-dashboard')); ?>" class="nav-link<?php echo is_current_page('dashboard') ? ' active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-text"><?php esc_html_e('Dashboard', 'wp-woocommerce-printify-sync'); ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-products')); ?>" class="nav-link<?php echo is_current_page('products') ? ' active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span class="nav-text"><?php esc_html_e('Products', 'wp-woocommerce-printify-sync'); ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-orders')); ?>" class="nav-link<?php echo is_current_page('orders') ? ' active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i>
            <span class="nav-text"><?php esc_html_e('Orders', 'wp-woocommerce-printify-sync'); ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-tickets')); ?>" class="nav-link<?php echo is_current_page('tickets') ? ' active' : ''; ?>">
            <i class="fas fa-ticket-alt"></i>
            <span class="nav-text"><?php esc_html_e('Tickets', 'wp-woocommerce-printify-sync'); ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-emails')); ?>" class="nav-link<?php echo is_current_page('emails') ? ' active' : ''; ?>">
            <i class="fas fa-envelope"></i>
            <span class="nav-text"><?php esc_html_e('Emails', 'wp-woocommerce-printify-sync'); ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-reports')); ?>" class="nav-link<?php echo is_current_page('reports') ? ' active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-text"><?php esc_html_e('Reports', 'wp-woocommerce-printify-sync'); ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-settings')); ?>" class="nav-link<?php echo is_current_page('settings') ? ' active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span class="nav-text"><?php esc_html_e('Settings', 'wp-woocommerce-printify-sync'); ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-tools')); ?>" class="nav-link<?php echo is_current_page('tools') ? ' active' : ''; ?>">
            <i class="fas fa-tools"></i>
            <span class="nav-text"><?php esc_html_e('Tools', 'wp-woocommerce-printify-sync'); ?></span>
        </a>
    </li>
    <?php if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') : ?>
        <li class="nav-item">
            <a href="<?php echo esc_url(admin_url('admin.php?page=postman')); ?>" class="nav-link<?php echo is_current_page('postman') ? ' active' : ''; ?>">
                <i class="fas fa-paper-plane"></i>
                <span class="nav-text"><?php esc_html_e('Postman', 'wp-woocommerce-printify-sync'); ?></span>
            </a>
        </li>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

/**
 * Check if current page matches
 *
 * @param string $page_slug Page slug to check
 * @return bool True if current page matches
 */
function is_current_page($page_slug) {
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    return strpos($current_page, $page_slug) !== false;
}