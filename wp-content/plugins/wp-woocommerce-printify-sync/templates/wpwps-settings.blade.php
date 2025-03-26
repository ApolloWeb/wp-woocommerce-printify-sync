<?php
defined('ABSPATH') || die('Direct access not allowed.');

if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wp-woocommerce-printify-sync'));
}

// Ensure WP admin environment
require_once(ABSPATH . 'wp-admin/admin.php');

// Load WP admin headers
require_once(ABSPATH . 'wp-admin/admin-header.php');
?>

<div class="wrap wpwps-container">
    <!-- Add basic content to verify template loading -->
    <h1><?php echo esc_html__('WP WooCommerce Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <!-- Rest of the template content -->
    <?php require_once(WPWPS_PLUGIN_DIR . 'templates/partials/settings-form.php'); ?>
</div>

<?php
// Add admin footer
require_once(ABSPATH . 'wp-admin/admin-footer.php');
?>