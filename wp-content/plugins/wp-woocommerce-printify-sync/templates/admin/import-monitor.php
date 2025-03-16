<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-import-monitor">
    <h1><?php _e('Import Monitor', 'wp-woocommerce-printify-sync'); ?></h1>

    <!-- System Health -->
    <div class="wpwps-health-panel">
        <h2><?php _e('System Health', 'wp-woocommerce-printify-sync'); ?></h2>
        <div class="health-grid">
            <?php foreach ($systemHealth as $key => $value): ?>
                <div class="health-item <?php echo esc_attr($value['status'] ?? ''); ?>">
                    <span class="label"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></span>
                    <span class="value"><?php echo esc_html($value['value'] ?? $value); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Active Imports -->
    <div class="wpwps-active-imports">
        <h2><?php _e('Active Imports', 'wp-woocommerce-printify-sync'); ?></h2>
        <?php if (empty($activeImports)): ?>
            <p class="no-imports"><?php _e('No active imports', 'wp-woocommerce-printify-sync'); ?></p>
        <?php else: ?>
            <div class="imports-grid">
                <?php foreach ($activeImports as $import): ?>
                    <div class="import-card">
                        <div class="progress-bar" 
                             style="width: <?php echo esc_attr($import['progress']); ?>%">
                        </div>
                        <div class="import-details">
                            <span class="batch-id"><?php echo esc_html($import['batch_id']); ?></span>
                            <span class="progress">
                                <?php echo esc_html($import['processed_items']); ?> / 
                                <?php echo esc_html($import['total_items']); ?>
                            </span>
                            <span class="rate">
                                <?php echo esc_html($import['current_rate']); ?> items/sec
                            </span>
                            <div class="actions">
                                <button class="button pause-import" 
                                        data-batch="<?php echo esc_attr($import['batch_id']); ?>">
                                    <?php _e('Pause', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Performance Metrics -->
    <div class="wpwps-performance">
        <h2><?php _e('Performance Metrics', 'wp-woocommerce-printify-sync'); ?></h2>
        <div class="metrics-grid">
            <?php foreach ($performanceMetrics as $key => $value): ?>
                <div class="metric-card">
                    <span class="metric-label">
                        <?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>
                    </span>
                    <span class="metric-value"><?php echo esc_html($value); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="performance-chart"></div>
    </div>

    <!-- Recent Imports -->
    <div class="wpwps-recent-imports">
        <h2><?php _e('Recent Imports', 'wp-woocommerce-printify-sync'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Batch ID', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Progress', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Success Rate', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Started', 'wp-woocommerce-printify-sync'); ?></th>
                    <th><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentImports as $import): ?>
                    <tr>
                        <td><?php echo esc_html($import['batch_id']); ?></td>
                        <td>
                            <span class="status-badge <?php echo esc_attr($import['status']); ?>">
                                <?php echo esc_html($import['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo esc_html($import['processed_items']); ?> / 
                            <?php echo esc_html($import['total_items']); ?>
                        </td>
                        <td><?php echo esc_html($import['success_rate']); ?>%</td>
                        <td><?php echo esc_html(human_time_diff(strtotime($import['started_at']))); ?> ago</td>
                        <td>
                            <button class="button view-details" 
                                    data-batch="<?php echo esc_attr($import['batch_id']); ?>">
                                <?php _e('View Details', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                            <?php if ($import['status'] === 'failed'): ?>
                                <button class="button retry-import" 
                                        data-batch="<?php echo esc_attr($import['batch_id']); ?>">
                                    <?php _e('Retry', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>