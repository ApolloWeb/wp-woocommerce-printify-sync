<?php
/**
 * API Call Logs Widget Template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets
 */
defined('ABSPATH') || exit;
?>
<div class="data-card">
    <div class="card-header">
        <h3>API Call Logs</h3>
        <div class="card-actions">
            <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($api_calls)): ?>
            <div class="empty-state">
                <p>No API call logs available.</p>
            </div>
        <?php else: ?>
            <div class="api-logs-table">
                <table class="printify-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Request</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($api_calls as $call): ?>
                            <tr>
                                <td><?php echo esc_html($call['id']); ?></td>
                                <td><?php echo esc_html($call['request']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $call['status'] === 'Success' ? 'success' : 'error'; ?>">
                                        <?php echo esc_html($call['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>