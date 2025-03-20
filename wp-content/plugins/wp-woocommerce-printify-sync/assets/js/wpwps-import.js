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
    
    // Retrieve Products button
    $('#retrieve-products').on('click', function() {
        const btn = $(this);
        const productType = $('#product_type').val();
        const syncMode = $('#sync_mode').val();
        
        // Show the retrieval status
        $('#product-retrieval-status').removeClass('d-none');
        $('#product-preview').addClass('d-none');
        
        // Reset progress bar
        $('#retrieval-progress-bar').css('width', '0%').attr('aria-valuenow', 0).text('0%');
        $('#retrieval-status-message').text('Retrieving products from Printify...');
        
        // Disable form controls while retrieving
        btn.prop('disabled', true);
        $('#product_type, #sync_mode').prop('disabled', true);
        
        // Animate progress bar (simulated progress while API call is in process)
        let progress = 0;
        const interval = setInterval(function() {
            progress += 5;
            if (progress > 90) {
                clearInterval(interval);
            }
            $('#retrieval-progress-bar').css('width', progress + '%').attr('aria-valuenow', progress).text(progress + '%');
        }, 300);
        
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_retrieve_products',
                nonce: wpwps.nonce,
                product_type: productType,
                sync_mode: syncMode
            },
            success: function(response) {
                // Clear the progress interval
                clearInterval(interval);
                
                // Complete the progress bar
                $('#retrieval-progress-bar').css('width', '100%').attr('aria-valuenow', 100).text('100%');
                
                // Re-enable form controls
                btn.prop('disabled', false);
                $('#product_type, #sync_mode').prop('disabled', false);
                
                if (response.success) {
                    // Hide the retrieval status after a brief delay to show completion
                    setTimeout(function() {
                        $('#product-retrieval-status').addClass('d-none');
                        
                        // Update the preview area
                        $('#products-count-message').text(response.data.total + ' products retrieved successfully and ready for import.');
                        
                        // Populate the products table
                        const products = response.data.products;
                        let tableHtml = '';
                        
                        products.forEach(function(product) {
                            const statusBadge = product.exists ? 
                                '<span class="badge bg-warning">Update</span>' : 
                                '<span class="badge bg-success">New</span>';
                            
                            tableHtml += '<tr>' +
                                '<td>' + product.title + '</td>' +
                                '<td>' + product.type + '</td>' +
                                '<td>' + product.variants + '</td>' +
                                '<td>' + statusBadge + '</td>' +
                                '</tr>';
                        });
                        
                        $('#products-preview-table').html(tableHtml);
                        
                        // Show the preview
                        $('#product-preview').removeClass('d-none');
                        
                        // Enable the import button
                        $('#start-import').prop('disabled', false);
                    }, 1000);
                } else {
                    // Hide the retrieval status
                    $('#product-retrieval-status').addClass('d-none');
                    
                    // Show error message
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                // Clear the progress interval
                clearInterval(interval);
                
                // Re-enable form controls
                btn.prop('disabled', false);
                $('#product_type, #sync_mode').prop('disabled', false);
                
                // Hide the retrieval status
                $('#product-retrieval-status').addClass('d-none');
                
                // Show detailed error message
                let errorMessage = 'An error occurred while retrieving products from Printify.';
                
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage += '\n\nDetails: ' + xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMessage += '\n\nDetails: ' + response.data.message;
                        }
                    } catch (e) {
                        errorMessage += '\n\nServer Response: ' + xhr.responseText;
                    }
                } else {
                    errorMessage += '\n\nError: ' + error;
                }
                
                console.error('Printify API Error:', error, xhr);
                alert(errorMessage);
            }
        });
    });
    
    // Confirm import start
    $('.import-form').on('submit', function(e) {
        if (!confirm('Are you sure you want to start importing products? This may take some time depending on how many products you have.')) {
            e.preventDefault();
        }
    });
});
