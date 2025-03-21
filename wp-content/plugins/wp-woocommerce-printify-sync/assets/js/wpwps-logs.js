/**
 * Logs page JavaScript.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

jQuery(document).ready(function($) {
    // Handle log level filter
    $('#filter-log-level').on('change', function() {
        const level = $(this).val();
        const search = $('#search-logs').val();
        window.location.href = wpwps_data.logs_url + '&log_level=' + level + (search ? '&search=' + encodeURIComponent(search) : '');
    });
    
    // Handle search
    $('#search-logs-btn').on('click', function() {
        const search = $('#search-logs').val();
        const level = $('#filter-log-level').val();
        window.location.href = wpwps_data.logs_url + 
            (level ? '&log_level=' + level : '') + 
            (search ? '&search=' + encodeURIComponent(search) : '');
    });
    
    $('#search-logs').on('keypress', function(e) {
        if (e.keyCode === 13) {
            const search = $(this).val();
            const level = $('#filter-log-level').val();
            window.location.href = wpwps_data.logs_url + 
                (level ? '&log_level=' + level : '') + 
                (search ? '&search=' + encodeURIComponent(search) : '');
        }
    });
    
    // Handle clear logs
    $('#clear-logs').on('click', function() {
        if (confirm(wpwps_logs.confirm_clear)) {
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
                        alert(response.data.message || wpwps_logs.clear_error);
                    }
                },
                error: function() {
                    alert(wpwps_logs.ajax_error);
                }
            });
        }
    });
    
    // Auto-refresh functionality
    let autoRefresh = false;
    let refreshInterval;
    
    $('#toggle-refresh').on('click', function() {
        const button = $(this);
        autoRefresh = !autoRefresh;
        
        if (autoRefresh) {
            button.addClass('active');
            button.html('<i class="fas fa-sync fa-spin"></i> ' + wpwps_logs.auto_refresh_on);
            
            // Start auto-refresh
            refreshInterval = setInterval(function() {
                window.location.reload();
            }, 30000); // Refresh every 30 seconds
        } else {
            button.removeClass('active');
            button.html('<i class="fas fa-sync"></i> ' + wpwps_logs.auto_refresh_off);
            
            // Stop auto-refresh
            clearInterval(refreshInterval);
        }
    });
});
