<?php
/**
 * Reports Sales template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Action buttons for the card
$card_actions = '
<a href="#" class="btn btn-sm btn-primary">
    <i class="fas fa-download me-1"></i> ' . esc_html__('Export CSV', 'wp-woocommerce-printify-sync') . '
</a>';

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <form class="row g-2" method="get">
            <input type="hidden" name="page" value="wpwprintifysync-reports">
            <input type="hidden" name="tab" value="sales">
            
            <div class="col-12 col-md-3 col-lg-2">
                <select name="report_period" class="form-select form-select-sm">
                    <option value=""><?php esc_html_e('All Periods', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="daily" <?php selected(isset($_GET['report_period']) ? $_GET['report_period'] : '', 'daily'); ?>><?php esc_html_e('Daily', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="monthly" <?php selected(isset($_GET['report_period']) ? $_GET['report_period'] : '', 'monthly'); ?>><?php esc_html_e('Monthly', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="yearly" <?php selected(isset($_GET['report_period']) ? $_GET['report_period'] : '', 'yearly'); ?>><?php esc_html_e('Yearly', 'wp-woocommerce-printify-sync'); ?></option>
                </select>
            </div>
            
            <div class="col-12 col-md-4 col-lg-4">
                <div class="input-group input-group-sm">
                    <input type="date" name="start_date" class="form-control" placeholder="<?php esc_attr_e('Start date...', 'wp-woocommerce-printify-sync'); ?>" value="<?php echo esc_attr(isset($_GET['start_date']) ? $_GET['start_date'] : ''); ?>">
                    <input type="date" name="end_date" class="form-control" placeholder="<?php esc_attr_e('End date...', 'wp-woocommerce-printify-sync'); ?>" value="<?php echo esc_attr(isset($_GET['end_date']) ? $_GET['end_date'] : ''); ?>">
                </div>
            </div>
            
            <div class="col-12 col-md-2 col-lg-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100"><?php esc_html_e('Filter', 'wp-woocommerce-printify-sync'); ?></button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="sales-chart-container position-relative" style="height: 300px;">
            <canvas id="salesChart"></canvas>
            <div class="chart-loader position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.7);">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden"><?php esc_html_e('Loading...', 'wp-woocommerce-printify-sync'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <h5><?php esc_html_e('Sales Data', 'wp-woocommerce-printify-sync'); ?></h5>
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th><?php esc_html_e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php esc_html_e('Orders', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php esc_html_e('Revenue', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sales_data)) : ?>
                    <?php foreach ($sales_data as $data) : ?>
                        <tr>
                            <td><?php echo esc_html($data['date']); ?></td>
                            <td><?php echo esc_html($data['orders']); ?></td>
                            <td><?php echo wp_kses_post($data['revenue']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3" class="text-center"><?php esc_html_e('No sales data found.', 'wp-woocommerce-printify-sync'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$card_content = ob_get_clean();

// Output the card with our content
do_action('wpwprintifysync_render_card', __('Sales Report', 'wp-woocommerce-printify-sync'), $card_content, array(
    'card_icon' => 'fa-chart-line',
    'card_actions' => $card_actions,
));