<?php
/**
 * Incoming Tickets Widget Template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets
 */
defined('ABSPATH') || exit;
?>
<div class="data-card">
    <div class="card-header">
        <h3>Support Tickets</h3>
        <div class="card-actions">
            <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <p>No support tickets available.</p>
            </div>
        <?php else: ?>
            <div class="tickets-list">
                <table class="printify-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo esc_html($ticket['id']); ?></td>
                                <td><?php echo esc_html($ticket['type']); ?></td>
                                <td><?php echo esc_html($ticket['subject']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $ticket['status'] === 'Open' ? 'warning' : 'success'; ?>">
                                        <?php echo esc_html($ticket['status']); ?>
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