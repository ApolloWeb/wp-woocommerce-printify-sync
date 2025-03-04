<?php
/**
 * Order Tracking Widget Template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets
 */
defined('ABSPATH') || exit;
?>
<div class="data-card">
    <div class="card-header">
        <h3>Order Tracking</h3>
        <div class="card-actions">
            <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <p>No tracking data available.</p>
            </div>
        <?php else: ?>
            <div class="order-tracking-list">
                <table class="printify-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Tracking Number</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo esc_html($order['id']); ?></td>
                                <td><?php echo esc_html($order['tracking_number']); ?></td>
                                <td>
                                    <a href="#" class="button button-small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>