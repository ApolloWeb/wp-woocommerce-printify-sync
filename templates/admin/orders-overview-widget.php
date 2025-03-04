<<<<<<< HEAD
<?php
/**
 * Orders Overview Widget Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Initialize orders with default values if not provided
$orders_today = isset($orders_today) ? $orders_today : 5;
$orders_week = isset($orders_week) ? $orders_week : 18;
$orders = isset($orders) ? $orders : [
    [
        'id' => '1089',
        'date' => '2025-03-04 07:15:22',
        'customer' => 'John Smith',
        'status' => 'fulfilled',
        'status_label' => 'Fulfilled',
        'total' => 42.99,
        'currency' => '$'
    ],
    [
        'id' => '1088',
        'date' => '2025-03-03 21:30:15',
        'customer' => 'Maria Garcia',
        'status' => 'processing',
        'status_label' => 'Processing',
        'total' => 67.50,
        'currency' => '$'
    ],
    [
        'id' => '1087',
        'date' => '2025-02-28 14:22:10',
        'customer' => 'Robert Johnson',
        'status' => 'shipped',
        'status_label' => 'Shipped',
        'total' => 29.99,
        'currency' => '$'
    ]
];

// Function to get appropriate status class
function get_status_class($status) {
    switch ($status) {
        case 'fulfilled':
        case 'completed':
            return 'success';
        case 'processing':
            return 'warning';
        case 'shipped':
            return 'primary';
        case 'canceled':
        case 'failed':
            return 'error';
        default:
            return 'info';
    }
}

// Orders summary
?>
<div class="orders-summary">
    <div class="orders-summary-item">
        <span class="summary-label">Today</span>
        <span class="summary-value"><?php echo esc_html($orders_today); ?></span>
    </div>
    <div class="orders-summary-item">
        <span class="summary-label">This Week</span>
        <span class="summary-value"><?php echo esc_html($orders_week); ?></span>
    </div>
    <a href="<?php echo admin_url('admin.php?page=printify-orders'); ?>" class="summary-action">
        View All Orders
    </a>
</div>

<table class="printify-table">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order) : ?>
        <tr>
            <td>#<?php echo esc_html($order['id']); ?></td>
            <td><?php echo esc_html(date('M j, Y', strtotime($order['date']))); ?></td>
            <td><?php echo esc_html($order['customer']); ?></td>
            <td>
                <span class="status-badge <?php echo get_status_class($order['status']); ?>">
                    <?php echo esc_html($order['status_label']); ?>
                </span>
            </td>
            <td><?php echo esc_html($order['currency'] . number_format($order['total'], 2)); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<style>
.orders-summary {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    background: #f8f9fa;
    padding: 12px 15px;
    border-radius: 4px;
    align-items: center;
}

.summary-label {
    display: block;
    font-size: 12px;
    color: #6c757d;
}

.summary-value {
    font-size: 18px;
    font-weight: 600;
}

.summary-action {
    color: #7f54b3;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.summary-action:hover {
    text-decoration: underline;
}
</style>
=======
<div>
    <p>Orders Today: <?php echo esc_html($orders_today); ?></p>
    <p>Orders This Week: <?php echo esc_html($orders_week); ?></p>
    <p>Orders This Month: <?php echo esc_html($orders_month); ?></p>
    <!-- Here you can include a graph/chart visualization of the orders data -->
</div>

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: </div>
#
#
# Commit Hash 16c804f
#
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
