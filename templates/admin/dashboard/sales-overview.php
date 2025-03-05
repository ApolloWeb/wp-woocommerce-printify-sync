<?php
/**
 * Dashboard sales overview
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$card_actions = '
<div class="btn-group">
    <button type="button" id="sales-period-selector" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        ' . esc_html__('This Week', 'wp-woocommerce-printify-sync') . '
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sales-period-selector">
        <li><a class="dropdown-item sales-period" data-period="day" href="#">' . esc_html__('Today', 'wp-woocommerce-printify-sync') . '</a></li>
        <li><a class="dropdown-item sales-period active" data-period="week" href="#">' . esc_html__('This Week', 'wp-woocommerce-printify-sync') . '</a></li>
        <li><a class="dropdown-item sales-period" data-period="month" href="#">' . esc_html__('This Month', 'wp-woocommerce-printify-sync') . '</a></li>
        <li><a class="dropdown-item sales-period" data-period="year" href="#">' . esc_html__('This Year', 'wp-woocommerce-printify-sync') . '</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item sales-period" data-period="custom" href="#">' . esc_html__('Custom Range', 'wp-woocommerce-printify-sync') . '</a></li>
    </ul>
</div>';

ob_start();
?>

<div class="sales-chart-container position-relative" style="height: 300px;">
    <canvas id="salesChart"></canvas>
    <div class="chart-loader position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.7);">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden"><?php esc_html_e('Loading...', 'wp-woocommerce-printify-sync'); ?></span>
        </div>
    </div>
</div>

<div id="sales-date-range" class="custom-date-range mt-3 d-none">
    <div class="row g-2 align-items-center">
        <div class="col-auto">
            <label for="sales-date-from" class="col-form-label"><?php esc_html_e('From:', 'wp-woocommerce-printify-sync'); ?></label>
        </div>
        <div class="col-auto">
            <input type="date" id="sales-date-from" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <label for="sales-date-to" class="col-form-label"><?php esc_html_e('To:', 'wp-woocommerce-printify-sync'); ?></label>
        </div>
        <div class="col-auto">
            <input type="date" id="sales-date-to" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <button id="apply-date-range" class="btn btn-sm btn-primary"><?php esc_html_e('Apply', 'wp-woocommerce-printify-sync'); ?></button>
        </div>
    </div>
</div>

<?php
$card_content = ob_get_clean();

// Output the card with our content
do_action('wpwprintifysync_render_card', __('Sales Overview', 'wp-woocommerce-printify-sync'), $card_content, array(
    'card_icon' => 'fa-chart-line',
    'card_actions' => $card_actions,
));