<?php
/**
 * Environment Indicator Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get environment setting
$environment = get_option('printify_sync_environment', 'production');
$is_dev = $environment === 'development';
?>

<div class="environment-indicator <?php echo $is_dev ? 'alert alert-warning' : 'alert alert-success'; ?>">
    <div class="environment-indicator-content">
        <i class="fas <?php echo $is_dev ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
        <span class="environment-label"><?php echo $is_dev ? 'Development' : 'Production'; ?> Environment</span>
        <?php if ($is_dev): ?>
            <span class="environment-details">Debug features are enabled. The Postman menu item is visible. Do not use in production.</span>
        <?php else: ?>
            <span class="environment-details">Debug features are disabled. The Postman menu item is hidden. Ready for live use.</span>
        <?php endif; ?>
    </div>
</div>

<style>
.environment-indicator {
    width: 100%;
    padding: 8px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-radius: 4px;
}

.environment-indicator-content {
    display: flex;
    align-items: center;
}

.environment-indicator i {
    margin-right: 8px;
    font-size: 16px;
}

.environment-label {
    font-weight: 600;
    font-size: 14px;
    margin-right: 10px;
}

.environment-details {
    font-size: 12px;
    opacity: 0.9;
}
</style>