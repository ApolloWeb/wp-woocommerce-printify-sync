/**
 * Product Import JavaScript
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

jQuery(document).ready(function($) {
    // Check import progress periodically if an import is running
    if ($('.import-status-panel').length > 0) {
        // Set up progress checker to run every 5 seconds
        const progressInterval = setInterval(checkImportProgress, 5000);
        
        function checkImportProgress() {
            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_check_import_progress',
                    nonce: wpwps.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Update progress bar
                        $('.progress-bar').css('width', data.progress + '%').attr('aria-valuenow', data.progress).text(data.progress + '%');
                        
                        // Update stats
                        $('.import-status-panel .badge.bg-primary').text(data.stats.total);
                        $('.import-status-panel .badge.bg-info').text(data.stats.processed);
                        $('.import-status-panel .badge.bg-success').text(data.stats.imported);
                        $('.import-status-panel .badge.bg-warning').text(data.stats.updated);
                        $('.import-status-panel .badge.bg-danger').text(data.stats.failed);
                        
                        // If import is complete, stop checking and reload the page
                        if (!data.is_running && data.stats.processed >= data.stats.total && data.stats.total > 0) {
                            clearInterval(progressInterval);
                            
                            // Show completion message
                            const alertHtml = '<div class="alert alert-success mt-3">' +
                                '<i class="fas fa-check-circle me-2"></i>' + 
                                'Import completed! Reloading page...' +
                                '</div>';
                            
                            $('.import-status-panel').append(alertHtml);
                            
                            // Reload the page after a short delay
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        }
                    }
                }
            });
        }
    }
    
    // Confirm import start
    $('.import-form').on('submit', function(e) {
        if (!confirm('Are you sure you want to start importing products? This may take some time depending on how many products you have.')) {
            e.preventDefault();
        }
    });
});
