<?php
/**
 * Logs template.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get log entries
$logger = new ApolloWeb\WPWooCommercePrintifySync\Services\Logger();
$log_entries = $logger->getLogEntries(
    isset($_GET['log_level']) ? sanitize_text_field($_GET['log_level']) : null,
    50,
    isset($_GET['search']) ? sanitize_text_field($_GET['search']) : ''
);
?>
<div class="wrap wpwps-admin-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="wp-heading-inline">
            <i class="fas fa-file-alt"></i> <?php echo esc_html__('Printify Sync - Logs', 'wp-woocommerce-printify-sync'); ?>
        </h1>
        
        <?php if (!empty($shop_name)) : ?>
        <div class="wpwps-shop-info">
            <span class="wpwps-shop-badge">
                <i class="fas fa-store"></i> <?php echo esc_html($shop_name); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <hr class="wp-header-end">
    
    <?php if (empty($shop_id)) : ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <?php esc_html_e('Your Printify Shop is not configured yet. Please go to the Settings page and set up your API connection.', 'wp-woocommerce-printify-sync'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings')); ?>" class="btn btn-primary ms-3">
            <i class="fas fa-cog"></i> <?php esc_html_e('Go to Settings', 'wp-woocommerce-printify-sync'); ?>
        </a>
    </div>
    <?php else : ?>
    
    <div class="wpwps-logs-container">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="wpwps-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php esc_html_e('System Logs', 'wp-woocommerce-printify-sync'); ?></h5>
                        <div class="form-inline">
                            <div class="input-group">
                                <input type="text" id="search-logs" class="form-control" placeholder="<?php esc_attr_e('Search logs...', 'wp-woocommerce-printify-sync'); ?>" value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="search-logs-btn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <select id="filter-log-level" class="form-select ms-2">
                                <option value=""><?php esc_html_e('All Levels', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="emergency" <?php selected(isset($_GET['log_level']) && $_GET['log_level'] === 'emergency'); ?>><?php esc_html_e('Emergency', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="alert" <?php selected(isset($_GET['log_level']) && $_GET['log_level'] === 'alert'); ?>><?php esc_html_e('Alert', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="critical" <?php selected(isset($_GET['log_level']) && $_GET['log_level'] === 'critical'); ?>><?php esc_html_e('Critical', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="error" <?php selected(isset($_GET['log_level']) && $_GET['log_level'] === 'error'); ?>><?php esc_html_e('Error', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="warning" <?php selected(isset($_GET['log_level']) && $_GET['log_level'] === 'warning'); ?>><?php esc_html_e('Warning', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="notice" <?php selected(isset($_GET['log_level']) && $_GET['log_level'] === 'notice'); ?>><?php esc_html_e('Notice', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="info" <?php selected(isset($_GET['log_level']) && $_GET['log_level'] === 'info'); ?>><?php esc_html_e('Info', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="debug" <?php selected(isset($_GET['log_level']) && $_GET['log_level'] === 'debug'); ?>><?php esc_html_e('Debug', 'wp-woocommerce-printify-sync'); ?></option>
                            </select>
                            
                            <button id="clear-logs" class="btn btn-danger ms-2">
                                <i class="fas fa-trash"></i> <?php esc_html_e('Clear Logs', 'wp-woocommerce-printify-sync'); ?>
                            </button>

                            <button id="toggle-refresh" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-sync"></i> <?php esc_html_e('Auto-refresh Off', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table wpwps-table" id="logs-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Timestamp', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php esc_html_e('Level', 'wp-woocommerce-printify-sync'); ?></th>
                                        <th><?php esc_html_e('Message', 'wp-woocommerce-printify-sync'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($log_entries)) : ?>
                                    <tr>
                                        <td colspan="3" class="text-center"><?php esc_html_e('No log entries found.', 'wp-woocommerce-printify-sync'); ?></td>
                                    </tr>
                                    <?php else : ?>
                                        <?php foreach ($log_entries as $entry) : ?>
                                        <tr class="log-level-<?php echo esc_attr($entry['level']); ?>">
                                            <td><?php echo esc_html($entry['timestamp']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo esc_attr($this->getLevelClass($entry['level'])); ?>">
                                                    <?php echo esc_html(strtoupper($entry['level'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo esc_html($entry['message']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle log level filter
    $('#filter-log-level').on('change', function() {
        const level = $(this).val();
        const search = $('#search-logs').val();
        window.location.href = '<?php echo esc_url(admin_url('admin.php?page=wpwps-logs')); ?>&log_level=' + level + (search ? '&search=' + encodeURIComponent(search) : '');
    });
    
    // Handle search
    $('#search-logs-btn, #search-logs').on('click keypress', function(e) {
        if (e.type === 'click' || e.keyCode === 13) {
            const search = $('#search-logs').val();
            const level = $('#filter-log-level').val();
            window.location.href = '<?php echo esc_url(admin_url('admin.php?page=wpwps-logs')); ?>' + 
                (level ? '&log_level=' + level : '') + 
                (search ? '&search=' + encodeURIComponent(search) : '');
        }
    });
    
    // Handle clear logs
    $('#clear-logs').on('click', function() {
        if (confirm('<?php esc_html_e('Are you sure you want to clear all logs? This action cannot be undone.', 'wp-woocommerce-printify-sync'); ?>')) {
            $.ajax({
                url: wpwps_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_clear_logs',
                    nonce: wpwps_data.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data.message || 'Error clearing logs.');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e('An error occurred while clearing logs.', 'wp-woocommerce-printify-sync'); ?>');
                }
            });
        }
    });

    // Handle auto-refresh toggle
    let autoRefresh = false;
    let refreshInterval;

    $('#toggle-refresh').on('click', function() {
        autoRefresh = !autoRefresh;
        $(this).html('<i class="fas fa-sync"></i> ' + (autoRefresh ? '<?php esc_html_e('Auto-refresh On', 'wp-woocommerce-printify-sync'); ?>' : '<?php esc_html_e('Auto-refresh Off', 'wp-woocommerce-printify-sync'); ?>'));

        if (autoRefresh) {
            refreshInterval = setInterval(function() {
                window.location.reload();
            }, 30000); // Refresh every 30 seconds
        } else {
            clearInterval(refreshInterval);
        }
    });
});
</script>

<?php
/**
 * Get the badge class for a log level.
 *
 * @param string $level Log level.
 * @return string Badge class.
 */
function getLevelClass($level) {
    switch ($level) {
        case 'emergency':
        case 'alert':
        case 'critical':
        case 'error':
            return 'danger';
        case 'warning':
            return 'warning';
        case 'notice':
            return 'info';
        case 'info':
            return 'success';
        case 'debug':
            return 'secondary';
        default:
            return 'secondary';
    }
}
?>
