<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-wrapper">
    <h1>Sync Logs</h1>

    <div class="wpwps-card">
        <div class="wpwps-card-header">
            <h2>Recent Activity</h2>
        </div>
        <div class="wpwps-card-body">
            <?php
            $logHelper = new \ApolloWeb\WPWooCommercePrintifySync\Helpers\LogHelper();
            $logs = $logHelper->getRecentLogs();
            
            foreach ($logs as $log) {
                echo '<div class="log-entry">';
                echo '<span class="log-time">' . esc_html($log->created_at) . '</span>';
                echo '<span class="log-message">' . esc_html($log->message) . '</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>