<?php
/**
 * Webhook Status Widget Template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets
 */
defined('ABSPATH') || exit;
?>
<div class="data-card">
    <div class="card-header">
        <h3>Webhook Status</h3>
        <div class="card-actions">
            <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($webhooks)): ?>
            <div class="empty-state">
                <p>No webhook data available.</p>
            </div>
        <?php else: ?>
            <div class="webhook-statuses">
                <table class="printify-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($webhooks as $webhook): ?>
                            <tr>
                                <td>#<?php echo esc_html($webhook['id']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $webhook['status'] === 'Active' ? 'success' : 'error'; ?>">
                                        <?php echo esc_html($webhook['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($webhook['response']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>