<?php
/**
 * Product Sync Summary Widget Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Initialize product sync data with default values if not provided
$sync_data = isset($sync_data) ? $sync_data : [
    'total_products' => 248,
    'synced_products' => 248,
    'pending_products' => 0,
    'last_sync' => '2025-03-04 06:30:15',
    'sync_status' => 'completed',
    'sync_message' => 'All products synchronized successfully',
    'recent_syncs' => [
        [
            'date' => '2025-03-04 06:30:15',
            'status' => 'success',
            'products' => 37,
            'message' => 'Added 12 new products, updated 25 products'
        ],
        [
            'date' => '2025-03-01 12:45:22',
            'status' => 'success',
            'products' => 15,
            'message' => 'Added 5 new products, updated 10 products'
        ],
        [
            'date' => '2025-02-25 09:15:30',
            'status' => 'partial',
            'products' => 42,
            'message' => 'Synced 40 products, 2 products failed'
        ]
    ]
];

// Function to get sync status class
function get_sync_status_class($status) {
    switch ($status) {
        case 'success':
        case 'completed':
            return 'success';
        case 'partial':
            return 'warning';
        case 'failed':
            return 'error';
        case 'in_progress':
            return 'info';
        default:
            return 'primary';
    }
}
?>

<div class="sync-summary">
    <div class="sync-stats">
        <div class="sync-stat-item">
            <div class="stat-value"><?php echo esc_html($sync_data['total_products']); ?></div>
            <div class="stat-label">Total Products</div>
        </div>
        <div class="sync-stat-item">
            <div class="stat-value"><?php echo esc_html($sync_data['synced_products']); ?></div>
            <div class="stat-label">Synced Products</div>
        </div>
        <div class="sync-stat-item">
            <div class="stat-value"><?php echo esc_html($sync_data['pending_products']); ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="sync-stat-item">
            <div class="stat-value"><?php echo human_time_diff(strtotime($sync_data['last_sync']), time()); ?></div>
            <div class="stat-label">Last Sync</div>
        </div>
    </div>
    
    <div class="sync-message">
        <span class="status-badge <?php echo get_sync_status_class($sync_data['sync_status']); ?>">
            <?php echo ucfirst($sync_data['sync_status']); ?>
        </span>
        <span class="message-text"><?php echo esc_html($sync_data['sync_message']); ?></span>
    </div>
    
    <div class="recent-syncs">
        <h5>Recent Sync History</h5>
        <table class="printify-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Products</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sync_data['recent_syncs'] as $sync) : ?>
                <tr>
                    <td><?php echo esc_html(date('M j, Y H:i', strtotime($sync['date']))); ?></td>
                    <td><?php echo esc_html($sync['products']); ?></td>
                    <td>
                        <span class="status-badge <?php echo get_sync_status_class($sync['status']); ?>">
                            <?php echo ucfirst($sync['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="sync-actions">
        <a href="<?php echo admin_url('admin.php?page=printify-products'); ?>" class="printify-btn btn-sm">
            <i class="fas fa-sync"></i> Sync Now
        </a>
    </div>
</div>

<style>
.sync-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.sync-stat-item {
    text-align: center;
    flex: 1;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    margin: 0 5px;
}

.sync-stat-item:first-child {
    margin-left: 0;
}

.sync-stat-item:last-child {
    margin-right: 0;
}

.sync-stat-item .stat-value {
    font-size: 18px;
    font-weight: 600;
    color: #7f54b3;
}

.sync-stat-item .stat-label {
    font-size: 12px;
    color: #6c757d;
}

.sync-message {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.sync-message .message-text {
    margin-left: 10px;
}

.recent-syncs h5 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 14px;
    font-weight: 600;
}

.sync-actions {
    margin-top: 15px;
    text-align: right;
}

@media (max-width: 782px) {
    .sync-stats {
        flex-wrap: wrap;
    }
    
    .sync-stat-item {
        min-width: 45%;
        margin-bottom: 10px;
    }
}
</style>