<?php
/**
 * Visitors by Country Dashboard Widget Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

use ApolloWeb\WPWooCommercePrintifySync\Analytics\VisitorTracker;

// Get visitor stats for last 30 days
$visitor_stats = VisitorTracker::getInstance()->getVisitorStatsByCountry(30);

// Get country names
$country_names = WC()->countries->get_countries();

// Current timestamp for display
$current_timestamp = '2025-03-05 19:22:34';
?>

<div class="wpwprintifysync-visitors-widget">
    <div class="wpwprintifysync-widget-header">
        <h3><?php _e('Visitor Statistics by Country', 'wp-woocommerce-printify-sync'); ?></h3>
        <span class="wpwprintifysync-widget-timestamp">
            <?php echo sprintf(__('Last 30 days (as of %s)', 'wp-woocommerce-printify-sync'), $current_timestamp); ?>
        </span>
    </div>
    
    <?php if (empty($visitor_stats)): ?>
        <p class="wpwprintifysync-no-data">
            <?php _e('No visitor data available yet.', 'wp-woocommerce-printify-sync'); ?>
        </p>
    <?php else: ?>
        <div class="wpwprintifysync-stats-chart">
            <canvas id="wpwprintifysync-country-chart" width="100%" height="200"></canvas>
        </div>
        
        <table class="wpwprintifysync-stats-table widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Country', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Visitors', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Conversions', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Rate', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visitor_stats as $stat): ?>
                    <?php 
                    $country_name = isset($country_names[$stat->country_code]) ? 
                        $country_names[$stat->country_code] : 
                        $stat->country_code;
                        
                    $conversion_rate = $stat->visits > 0 ? 
                        round(($stat->conversions / $stat->visits) * 100, 2) : 
                        0;
                    ?>
                    <tr>
                        <td>
                            <img src="<?php echo esc_url(WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/flags/' . strtolower($stat->country_code) . '.png'); ?>" 
                                 alt="<?php echo esc_attr($stat->country_code); ?>" 
                                 class="wpwprintifysync-country-flag" />
                            <?php echo esc_html($country_name); ?>
                        </td>
                        <td><?php echo number_format_i18n($stat->visits); ?></td>
                        <td><?php echo number_format_i18n($stat->conversions); ?></td>
                        <td><?php echo $conversion_rate; ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="wpwprintifysync-view-all">
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-analytics')); ?>" class="button button-secondary">
                <?php _e('View Detailed Analytics', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                if (typeof Chart !== 'undefined') {
                    var ctx = document.getElementById('wpwprintifysync-country-chart').getContext('2d');
                    
                    // Prepare chart data
                    var countries = [];
                    var visitors = [];
                    var conversions = [];
                    var backgroundColors = [
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(255, 159, 64, 0.6)',
                        'rgba(199, 199, 199, 0.6)'
                    ];
                    
                    <?php foreach (array_slice($visitor_stats, 0, 7) as $index => $stat): ?>
                        countries.push('<?php echo isset($country_names[$stat->country_code]) ? esc_js($country_names[$stat->country_code]) : esc_js($stat->country_code); ?>');
                        visitors.push(<?php echo esc_js($stat->visits); ?>);
                        conversions.push(<?php echo esc_js($stat->conversions); ?>);
                    <?php endforeach; ?>
                    
                    // Create chart
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: countries,
                            datasets: [{
                                label: '<?php _e('Visitors', 'wp-woocommerce-printify-sync'); ?>',
                                data: visitors,
                                backgroundColor: backgroundColors,
                                borderColor: backgroundColors.map(color => color.replace('0.6', '1')),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                } else {
                    $('#wpwprintifysync-country-chart').replaceWith(
                        '<p><?php _e('Chart.js is required for charts. Please include it in your admin.', 'wp-woocommerce-printify-sync'); ?></p>'
                    );
                }
            });
        </script>
    <?php endif; ?>
</div>

<style>
    .wpwprintifysync-visitors-widget {
        position: relative;
    }
    .wpwprintifysync-widget-header {
        margin-bottom: 15px;
    }
    .wpwprintifysync-widget-header h3 {
        margin: 0;
        padding: 0;
    }
    .wpwprintifysync-widget-timestamp {
        font-size: 12px;
        color: #757575;
    }
    .wpwprintifysync-stats-chart {
        margin-bottom: 15px;
        height: 200px;
    }
    .wpwprintifysync-stats-table {
        margin-bottom: 15px;
    }
    .wpwprintifysync-country-flag {
        width: 16px;
        height: 11px;
        margin-right: 5px;
        vertical-align: middle;
    }
    .wpwprintifysync-no-data {
        text-align: center;
        padding: 20px;
        color: #757575;
    }
    .wpwprintifysync-view-all {
        text-align: right;
    }
</style>