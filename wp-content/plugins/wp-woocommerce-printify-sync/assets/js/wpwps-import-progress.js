jQuery(document).ready(function($) {
    // Initialize variables
    let progressInterval = null;
    const shopId = wpwps_data.shop_id;
    
    // Initialize progress tracking
    function initProgressTracking() {
        const progressContainer = $('#import-progress-container');
        if (progressContainer.length === 0) return;
        
        // Show progress container
        progressContainer.show();
        
        // Start polling for progress updates
        progressInterval = setInterval(checkImportProgress, 3000);
    }
    
    // Check import progress
    function checkImportProgress() {
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'get_import_progress',
                nonce: wpwps_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateProgressUI(response.data);
                    
                    // If import is completed, stop polling
                    if (response.data.status === 'completed') {
                        clearInterval(progressInterval);
                        setTimeout(function() {
                            // Refresh product list
                            if (typeof fetchProducts === 'function') {
                                fetchProducts(true);
                            }
                        }, 2000);
                    }
                } else {
                    // No import in progress or error
                    $('#import-progress-container').hide();
                    clearInterval(progressInterval);
                }
            },
            error: function() {
                // Error, stop polling
                clearInterval(progressInterval);
                $('#import-progress-container').hide();
            }
        });
    }
    
    // Update progress UI
    function updateProgressUI(data) {
        const progressContainer = $('#import-progress-container');
        
        if (data.status === 'completed') {
            progressContainer.html(`
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Import completed successfully!
                </div>
            `);
            
            // Hide progress after 5 seconds
            setTimeout(function() {
                progressContainer.fadeOut();
            }, 5000);
            
            return;
        }
        
        if (data.status === 'in_progress' && data.progress) {
            const progress = data.progress;
            const percentage = progress.percentage || 0;
            
            progressContainer.html(`
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-sync fa-spin me-2"></i> Import Progress</h6>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                role="progressbar" 
                                style="width: ${percentage}%" 
                                aria-valuenow="${percentage}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                ${percentage}%
                            </div>
                        </div>
                        <div class="small text-muted">
                            Imported ${progress.imported} of ${progress.total} products
                        </div>
                    </div>
                </div>
            `);
        }
    }
    
    // Add progress tracking to bulk import button
    function attachBulkImportProgress() {
        const bulkImportButton = $('#import-selected');
        
        if (bulkImportButton.length === 0) return;
        
        // Add progress container after the button
        if ($('#import-progress-container').length === 0) {
            bulkImportButton.closest('.card-header').after(`
                <div id="import-progress-container" style="display: none;" class="p-3"></div>
            `);
        }
        
        // Override the original click handler
        const originalClick = bulkImportButton.attr('onclick');
        
        bulkImportButton.off('click').on('click', function() {
            // Show progress container right after click
            $('#import-progress-container').show().html(`
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-sync fa-spin me-2"></i> Starting Import...</h6>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                role="progressbar" 
                                style="width: 0%" 
                                aria-valuenow="0" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                0%
                            </div>
                        </div>
                        <div class="small text-muted">
                            Preparing to import products...
                        </div>
                    </div>
                </div>
            `);
            
            // Start progress tracking
            initProgressTracking();
            
            // Execute the original handler (which will start the import)
            if (typeof importSelectedProducts === 'function') {
                importSelectedProducts();
            }
        });
    }
    
    // Initialize on page load
    attachBulkImportProgress();
    
    // Check if there's already an import in progress
    checkImportProgress();
});
