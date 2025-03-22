<?php
/**
 * Dashboard template.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

global $wpwps_container;
$activity_service = $wpwps_container->get('activity_service');
$recent_activities = $activity_service->getActivities(5);

$api_status = 'unknown';
if (!empty($shop_id)) {
    $api_client = $wpwps_container->get('api_client');
    $test_connection = $api_client->testConnection();
    $api_status = is_wp_error($test_connection) ? 'error' : 'healthy';
}

$email_queue = 0;
global $wpdb;
$email_queue = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue WHERE status = 'pending'");
?>

<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-admin-wrap">
    <?php require WPWPS_PLUGIN_DIR . 'templates/partials/dashboard/wpwps-header.php'; ?>
    
    <div class="wpwps-content">
        <div class="wpwps-grid">
            <?php
            $sections = ['stats', 'revenue', 'activity', 'queue'];
            foreach ($sections as $section) {
                require WPWPS_PLUGIN_DIR . "templates/partials/dashboard/wpwps-{$section}.php";
            }
            ?>
        </div>
    </div>
</div>
