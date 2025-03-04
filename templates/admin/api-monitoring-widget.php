<?php
/**
 * API Monitoring Widget
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Sample API monitoring data - would be replaced with real data
$api_stats = isset($api_stats) ? $api_stats : [
    'today' => [
        'total' => 42,
        'successful' => 40,
        'failed' => 2,
        'response_time' => 0.87, // seconds
    ],
    'this_week' => [
        'total' => 186,
        'successful' => 178,
        'failed' => 8,
        'response_time' => 0.92, // seconds
    ],
    'rate_limit' => [
        'limit' => 500,
        'remaining' => 458,
        'reset' => time() + 3600 // 1 hour from now
    ]
];

// Sample recent API calls
$recent_api_calls = isset($recent_api_calls) ? $recent_api_calls : [
    [
        'endpoint' => 'GET /shops',
        'status' => 'success',
        'code' => 200,
        'time' => '2025-03-04 09:05:22',
        'duration' => 0.75,
        'message' => 'OK'
    ],
    [
        'endpoint' => 'GET /products/123456',
        'status' => 'success',
        'code' => 200,
        'time' => '2025-03-04 08:55:15',
        'duration' => 0.82,
        'message' => 'OK'
    ],
    [
        'endpoint' => 'POST /orders/create',
        'status' => 'success',
        'code' => 201,
        'time' => '2025-03-04 08:45:10',
        'duration' => 1.02,
        'message' => 'Created'
    ],
    [
        'endpoint' => 'GET /catalog',
        'status' => 'error',
        'code' => 429,
        'time' => '2025-03-04 08:30:05',
        'duration' => 0.43,
        'message' => 'Too Many Requests'
    ],
    [
        'endpoint' => 'GET /providers',
        'status' => 'success',
        'code' => 200,
        'time' => '2025-03-04 08:15:30',
        'duration' => 0.65,
        'message' => 'OK'
    ]
];

// Calculate rate limit percentage
$rate_limit_used = $api_stats['rate_limit']['limit'] - $api_stats['rate_limit']['remaining'];
$rate_limit_percent = round(($rate_limit_used / $api_stats['rate_limit']['limit']) * 100);
$rate_limit_class = $rate_limit_percent < 50 ? 'good' : ($rate_limit_percent < 80 ? 'warning' : 'critical');

// Calculate success rate
$success_rate = $api_stats['today']['total'] > 0 
    ? round(($api_stats['today']['successful'] / $api_stats['today']['total']) * 100) 
    : 0;
$success_rate_class = $success_rate > 95 ? 'good' : ($success_rate > 80 ? 'warning' : 'critical');

// Format time until rate limit reset
$reset_minutes = floor(($api_stats['rate_limit']['reset'] - time()) / 60);
$reset_seconds = ($api_stats['rate_limit']['reset'] - time()) % 60;
$reset_time = $reset_minutes . 'm ' . $reset_seconds . 's';
?>

<div class="api-monitoring-container">
    <div class="api-stats">
        <div class="api-stat-row">
            <div class="api-stat">
                <div class="api-stat-value">
                    <?php echo $api_stats['today']['total']; ?>
                    <span class="api-stat-unit">calls</span>
                </div>
                <div class="api-stat-label">Today's API Calls</div>
            </div>
            
            <div class="api-stat">
                <div class="api-stat-value <?php echo $success_rate_class; ?>">
                    <?php echo $success_rate; ?>
                    <span class="api-stat-unit">%</span>
                </div>
                <div class="api-stat-label">Success Rate</div>
            </div>
            
            <div class="api-stat">
                <div class="api-stat-value">
                    <?php echo number_format($api_stats['today']['response_time'], 2); ?>
                    <span class="api-stat-unit">sec</span>
                </div>
                <div class="api-stat-label">Avg. Response Time</div>
            </div>
        </div>
        
        <div class="api-rate-limit">
            <div class="rate-limit-header">
                <span class="rate-limit-title">API Rate Limit Usage</span>
                <span class="rate-limit-reset">Resets in: <?php echo $reset_time; ?></span>
            </div>
            <div class="rate-limit-bar-container">
                <div class="rate-limit-bar <?php echo $rate_limit_class; ?>" style="width: <?php echo $rate_limit_percent; ?>%"></div>
            </div>
            <div class="rate-limit-stats">
                <span><?php echo $rate_limit_used; ?> / <?php echo $api_stats['rate_limit']['limit']; ?> calls used</span>
                <span><?php echo $api_stats['rate_limit']['remaining']; ?> remaining</span>
            </div>
        </div>
    </div>
    
    <div class="recent-api-calls">
        <h4>Recent API Calls</h4>
        <div class="api-calls-table-container">
            <table class="printify-table compact">
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_api_calls as $call) : ?>
                    <tr>
                        <td>
                            <code><?php echo esc_html($call['endpoint']); ?></code>
                        </td>
                        <td>
                            <?php if ($call['status'] === 'success') : ?>
                                <span class="status-badge success">
                                    <?php echo esc_html($call['code']); ?>
                                </span>
                            <?php else : ?>
                                <span class="status-badge error" title="<?php echo esc_attr($call['message']); ?>">
                                    <?php echo esc_html($call['code']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(human_time_diff(strtotime($call['time']), time()) . ' ago'); ?></td>
                        <td>
                            <span class="duration <?php echo $call['duration'] > 1 ? 'slow' : 'fast'; ?>">
                                <?php echo number_format($call['duration'], 2); ?>s
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="api-monitoring-actions">
        <a href="<?php echo admin_url('admin.php?page=printify-logs'); ?>" class="printify-btn btn-sm">
            <i class="fas fa-file-alt"></i> View Full Logs
        </a>
        <button id="testApiConnection" class="printify-btn btn-outline btn-sm">
            <i class="fas fa-plug"></i> Test Connection
        </button>
    </div>
</div>

<style>
.api-monitoring-container {
    display: flex;
    flex-direction: column;
}

.api-stats {
    margin-bottom: 15px;
}

.api-stat-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.api-stat {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 6px;
    text-align: center;
    flex: 1;
    margin: 0 5px;
}

.api-stat:first-child {
    margin-left: 0;
}

.api-stat:last-child {
    margin-right: 0;
}

.api-stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #212529;
    line-height: 1.2;
}

.api-stat-value .api-stat-unit {
    font-size: 14px;
    font-weight: normal;
}

.api-stat-value.good {
    color: #46b450;
}

.api-stat-value.warning {
    color: #ffb900;
}

.api-stat-value.critical {
    color: #dc3232;
}

.api-stat-label {
    font-size: 12px;
    color: #6c757d;
    margin-top: 4px;
}

.api-rate-limit {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 6px;
}

.rate-limit-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.rate-limit-title {
    font-weight: 600;
    font-size: 14px;
}

.rate-limit-reset {
    font-size: 12px;
    color: #6c757d;
}

.rate-limit-bar-container {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.rate-limit-bar {
    height: 100%;
    border-radius: 4px;
}

.rate-limit-bar.good {
    background-color: #46b450;
}

.rate-limit-bar.warning {
    background-color: #ffb900;
}

.rate-limit-bar.critical {
    background-color: #dc3232;
}

.rate-limit-stats {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #6c757d;
}

.recent-api-calls {
    margin-top: 15px;
    margin-bottom: 15px;
}

.recent-api-calls h4 {
    font-size: 14px;
    margin-top: 0;
    margin-bottom: 10px;
    font-weight: 600;
}

.api-calls-table-container {
    max-height: 240px;
    overflow-y: auto;
}

.printify-table.compact th,
.printify-table.compact td {
    padding: 6px 10px;
    font-size: 13px;
}

.printify-table code {
    background: #f8f9fa;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 12px;
}

.duration {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.duration.fast {
    background: rgba(70, 180, 80, 0.1);
    color: #46b450;
}

.duration.slow {
    background: rgba(255, 185, 0, 0.1);
    color: #ffb900;
}

.api-monitoring-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
    border-top: 1px solid #e9ecef;
    padding-top: 15px;
}

@media (max-width: 782px) {
    .api-stat-row {
        flex-wrap: wrap;
    }
    
    .api-stat {
        min-width: 45%;
        margin-bottom: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set up API connection test button
    document.getElementById('testApiConnection').addEventListener('click', function() {
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
        this.disabled = true;
        
        // Simulate API test
        setTimeout(() => {
            this.innerHTML = '<i class="fas fa-check"></i> Connection OK';
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-plug"></i> Test Connection';
                this.disabled = false;
            }, 2000);
        }, 1500);
    });
    
    // Update the countdown timer
    const resetElement = document.querySelector('.rate-limit-reset');
    let remainingTime = <?php echo $api_stats['rate_limit']['reset'] - time(); ?>;
    
    function updateTimer() {
        remainingTime--;
        
        if (remainingTime <= 0) {
            resetElement.textContent = 'Resetting...';
            return;
        }
        
        const minutes = Math.floor(remainingTime / 60);
        const seconds = remainingTime % 60;
        resetElement.textContent = `Resets in: ${minutes}m ${seconds}s`;
    }
    
    // Update every second
    setInterval(updateTimer, 1000);
});
</script>