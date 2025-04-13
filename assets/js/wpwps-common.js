/**
 * WP WooCommerce Printify Sync - Common JavaScript
 * Shared functionality across all admin pages
 */
(function($) {
    'use strict';

    // DOM ready
    $(function() {
        // Refresh data button functionality
        $('#refresh-data').on('click', function() {
            const button = $(this);
            const originalHtml = button.html();
            
            // Show loading state
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
            
            // Make AJAX request to refresh data
            $.ajax({
                url: wpwps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_refresh_data',
                    nonce: wpwps_ajax.nonce,
                    current_page: wpwps_ajax.current_page
                },
                success: function(response) {
                    // Get the toast container, create if it doesn't exist
                    let toastContainer = $('.toast-container');
                    if (toastContainer.length === 0) {
                        $('body').append('<div class="toast-container position-fixed top-0 end-0 p-3"></div>');
                        toastContainer = $('.toast-container');
                    }
                    
                    // Create toast ID and HTML
                    const toastId = 'toast-' + Date.now();
                    const toastHtml = `
                        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="toast-header">
                                <span class="bg-success rounded me-2" style="width: 15px; height: 15px;"></span>
                                <strong class="me-auto">Data Refreshed</strong>
                                <small>${new Date().toLocaleTimeString()}</small>
                                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                            <div class="toast-body">
                                ${response.success ? response.data.message : 'Data has been refreshed successfully.'}
                            </div>
                        </div>
                    `;
                    
                    // Add and show toast
                    toastContainer.append(toastHtml);
                    const toast = new bootstrap.Toast(document.getElementById(toastId), {
                        animation: true,
                        autohide: true,
                        delay: 3000
                    });
                    toast.show();
                    
                    // Reload page if refresh was successful
                    if (response.success && response.data.reload) {
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                },
                error: function() {
                    alert('An error occurred while refreshing data.');
                },
                complete: function() {
                    // Reset button
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });
    });
})(jQuery);
