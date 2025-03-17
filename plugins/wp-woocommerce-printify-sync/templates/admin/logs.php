<?php
/**
 * Admin Log Viewer Template
 *
 * @var array $logs
 * @var array $pagination
 * @var array $stats
 * @var array $types
 * @var string $currentType
 * @var string $search
 */
?>

<div class="wrap wpwps-logs">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="fas fa-clipboard-list me-2"></i>
            <?php echo esc_html(get_admin_page_title()); ?>
        </h1>
        <div class="header-actions">
            <button class="wpwps-btn wpwps-btn-success me-2" id="exportLogs">
                <i class="fas fa-download me-1"></i>
                <?php _e('Export Logs', 'wp-woocommerce-printify-sync'); ?>
            </button>
            <button class="wpwps-btn wpwps-btn-danger" id="clearLogs">
                <i class="fas fa-trash me-1"></i>
                <?php _e('Clear Logs', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <?php foreach ($stats as $key => $stat): ?>
            <div class="col-md-6 col-xl-3">
                <div class="wpwps-card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-<?php echo esc_attr($stat['color']); ?>">
                                <i class="fas fa-<?php echo esc_attr($stat['icon']); ?>"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1"><?php echo esc_html($stat['label']); ?></h6>
                                <h3 class="mb-0"><?php echo esc_html($stat['value']); ?></h3>
                            </div>
                        </div>
                        <?php if (isset($stat['trend'])): ?>
                            <div class="mt-3">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-<?php echo esc_attr($stat['color']); ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo esc_attr($stat['percentage']); ?>%">
                                    </div>
                                </div>
                                <small class="mt-2 d-block">
                                    <?php echo esc_html($stat['description']); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <div class="wpwps-card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <div class="btn-group" role="group">
                        <?php foreach ($types as $type => $label): ?>
                            <a href="<?php echo esc_url(add_query_arg('type', $type)); ?>" 
                               class="btn btn-outline-secondary <?php echo $currentType === $type ? 'active' : ''; ?>">
                                <?php echo esc_html($label); ?>
                                <span class="badge bg-secondary ms-1">
                                    <?php echo esc_html($stats[$type]['count'] ?? 0); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <form class="d-flex gap-2">
                        <input type="hidden" name="page" value="wpwps-logs">
                        <input type="hidden" name="type" value="<?php echo esc_attr($currentType); ?>">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   name="s" 
                                   class="form-control" 
                                   placeholder="<?php esc_attr_e('Search logs...', 'wp-woocommerce-printify-sync'); ?>"
                                   value="<?php echo esc_attr($search); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <?php _e('Search', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="wpwps-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 160px;"><?php _e('Timestamp', 'wp-woocommerce-printify-sync'); ?></th>
                        <th style="width: 100px;"><?php _e('Level', 'wp-woocommerce-printify-sync'); ?></th>
                        <th style="width: 150px;"><?php _e('Component', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Message', 'wp-woocommerce-printify-sync'); ?></th>
                        <th style="width: 100px;"><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <div class="empty-state">
                                    <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                    <h5><?php _e('No Logs Found', 'wp-woocommerce-printify-sync'); ?></h5>
                                    <p class="text-muted">
                                        <?php _e('No logs match your search criteria.', 'wp-woocommerce-printify-sync'); ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($logs as $log): ?>
                        <tr class="<?php echo $log['level'] === 'error' ? 'table-danger' : ''; ?>">
                            <td>
                                <div class="d-flex flex-column">
                                    <span><?php echo esc_html($log['date']); ?></span>
                                    <small class="text-muted">
                                        <?php echo esc_html(human_time_diff(strtotime($log['date']))); ?> ago
                                    </small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo esc_attr($log['level_color']); ?>">
                                    <?php echo esc_html(ucfirst($log['level'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo esc_html($log['component']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="log-message">
                                    <?php echo wp_kses_post($log['message']); ?>
                                    <?php if (!empty($log['context'])): ?>
                                        <button class="btn btn-link btn-sm view-context p-0 ms-2">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <div class="log-context d-none">
                                            <pre><code><?php echo esc_html(json_encode($log['context'], JSON_PRETTY_PRINT)); ?></code></pre>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if (!empty($log['context'])): ?>
                                        <button type="button" class="btn btn-outline-secondary view-context" title="<?php esc_attr_e('View Details', 'wp-woocommerce-printify-sync'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (!empty($log['related_url'])): ?>
                                        <a href="<?php echo esc_url($log['related_url']); ?>" 
                                           class="btn btn-outline-primary" 
                                           target="_blank"
                                           title="<?php esc_attr_e('View Related', 'wp-woocommerce-printify-sync'); ?>">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Context Modal -->
    <div class="modal fade" id="contextModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php _e('Log Details', 'wp-woocommerce-printify-sync'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre><code class="context-content"></code></pre>
                </div>
            </div>
        </div>
    </div>
</div>