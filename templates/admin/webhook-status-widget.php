<<<<<<< HEAD
<?php
/**
 * Webhook Status Widget Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Initialize webhook data with default values if not provided
$webhook_url = isset($webhook_url) ? $webhook_url : site_url('/wp-json/printify-sync/v1/webhook');
$webhooks = isset($webhooks) ? $webhooks : [
    [
        'event' => 'Product Updated',
        'status' => 'active',
        'status_label' => 'Active',
        'last_triggered' => '2025-03-04 05:00:15'
    ],
    [
        'event' => 'Order Created',
        'status' => 'active',
        'status_label' => 'Active',
        'last_triggered' => '2025-03-03 21:30:10'
    ],
    [
        'event' => 'Shipping Update',
        'status' => 'not_configured',
        'status_label' => 'Not Configured',
        'last_triggered' => null
    ]
];

// Function to get webhook status class
function get_webhook_status_class($status) {
    switch ($status) {
        case 'active':
            return 'success';
        case 'inactive':
            return 'error';
        case 'not_configured':
            return 'warning';
        default:
            return 'info';
    }
}

// Format a timestamp for display or return "Never" if null
function format_webhook_timestamp($timestamp) {
    if (empty($timestamp)) {
        return 'Never';
    }
    
    return human_time_diff(strtotime($timestamp), time()) . ' ago';
}
?>

<div class="webhook-status">
    <div class="webhook-url-container">
        <strong>Webhook URL:</strong> 
        <code><?php echo esc_html($webhook_url); ?></code>
        <button class="copy-webhook-url" title="Copy URL" onclick="copyWebhookUrl()">
            <i class="fas fa-copy"></i>
        </button>
    </div>
    
    <table class="printify-table">
        <thead>
            <tr>
                <th>Event</th>
                <th>Status</th>
                <th>Last Triggered</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($webhooks as $webhook) : ?>
            <tr>
                <td><?php echo esc_html($webhook['event']); ?></td>
                <td>
                    <span class="status-badge <?php echo get_webhook_status_class($webhook['status']); ?>">
                        <?php echo esc_html($webhook['status_label']); ?>
                    </span>
                </td>
                <td><?php echo esc_html(format_webhook_timestamp($webhook['last_triggered'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
.webhook-url-container {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
}

.webhook-url-container code {
    background: #ffffff;
    padding: 4px 8px;
    border-radius: 3px;
    margin: 0 10px;
    border: 1px solid #e9ecef;
    flex: 1;
    word-break: break-all;
}

.copy-webhook-url {
    background: transparent;
    border: none;
    cursor: pointer;
    color: #7f54b3;
    padding: 5px;
    border-radius: 3px;
}

.copy-webhook-url:hover {
    background: rgba(127, 84, 179, 0.1);
}
</style>

<script>
function copyWebhookUrl() {
    const webhookUrl = '<?php echo esc_js($webhook_url); ?>';
    navigator.clipboard.writeText(webhookUrl).then(function() {
        alert('Webhook URL copied to clipboard!');
    });
}
</script>
=======
<div class="widget">
    <h4 class="card-title">Webhook Status</h4>
    <canvas id="webhook-status-chart"></canvas>
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
