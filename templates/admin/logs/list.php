<?php
/**
 * Log list template part
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;
?>

<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th class="manage-column column-timestamp"><?php _e('Timestamp', 'wp-woocommerce-printify-sync'); ?></th>
            <th class="manage-column column-level"><?php _e('Level', 'wp-woocommerce-printify-sync'); ?></th>
            <th class="manage-column column-user"><?php _e('User', 'wp-woocommerce-printify-sync'); ?></th>
            <th class="manage-column column-message"><?php _e('Message', 'wp-woocommerce-printify-sync'); ?></th>
            <th class="manage-column column-context"><?php _e('Context', 'wp-woocommerce-printify-sync'); ?></th>
            <th class="manage-column column-actions"><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($logs)): ?>
            <tr>
                <td colspan="6"><?php _e('No logs found.', 'wp-woocommerce-printify-sync'); ?></td>
            </tr>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="timestamp column-timestamp">
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?>
                    </td>
                    <td class="level column-level">
                        <span class="log-level log-level-<?php echo esc_attr($log->level); ?>">
                            <?php echo esc_html(ucfirst($log->level)); ?>
                        </span>
                    </td>
                    <td class="user column-user">
                        <?php echo esc_html($log->created_by); ?>
                    </td>
                    <td class="message column-message">
                        <?php echo esc_html($log->message); ?>
                    </td>
                    <td class="context column-context">
                        <?php 
                        $context = is_string($log->context) ? json_decode($log->context, true) : $log->context;
                        if (is_array($context) && !empty($context)) {
                            echo '<a href="#" class="toggle-context" data-log-id="' . esc_attr($log->id) . '">' . __('Show Context', 'wp-woocommerce-printify-sync') . '</a>';
                            echo '<div id="context-' . esc_attr($log->id) . '" class="context-data" style="display: none;">';
                            echo '<pre>' . esc_html(wp_json_encode($context, JSON_PRETTY_PRINT)) . '</pre>';
                            echo '</div>';
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td class="actions column-actions">
                        <a href="#" class="log-action copy-log" data-log-id="<?php echo esc_attr($log->id); ?>" title="<?php esc_attr_e('Copy log entry', 'wp-woocommerce-printify-sync'); ?>">
                            <span class="dashicons dashicons-clipboard"></span>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Toggle context visibility
        $('.toggle-context').on('click', function(e) {
            e.preventDefault();
            
            var logId = $(this).data('log-id');
            var contextDiv = $('#context-' + logId);
            
            if (contextDiv.is(':visible')) {
                contextDiv.hide();
                $(this).text('<?php _e('Show Context', 'wp-woocommerce-printify-sync'); ?>');
            } else {
                contextDiv.show();
                $(this).text('<?php _e('Hide Context', 'wp-woocommerce-printify-sync'); ?>');
            }
        });
        
        // Copy log entry
        $('.copy-log').on('click', function(e) {
            e.preventDefault();
            
            var logId = $(this).data('log-id');
            var rowData = $(this).closest('tr').find('td').map(function() {
                return $(this).text().trim();
            }).get().join(' | ');
            
            // Create a temporary textarea to copy from
            var textarea = $('<textarea>').val(rowData).appendTo('body').select();
            
            try {
                document.execCommand('copy');
                alert('<?php _e('Log entry copied to clipboard', 'wp-woocommerce-printify-sync'); ?>');
            } catch (err) {
                alert('<?php _e('Failed to copy log entry. Please try again.', 'wp-woocommerce-printify-sync'); ?>');
            }
            
            textarea.remove();
        });
    });
</script>