/**
 * Products page JavaScript for WP WooCommerce Printify Sync
 */
jQuery(document).ready(function($) {
    'use strict';
    
    var importButton = $('#wpwps-import-products');
    var clearButton = $('#wpwps-clear-products');
    var progressContainer = $('#wpwps-import-progress');
    var progressBar = progressContainer.find('.wpwps-progress-bar');
    var progressPercent = progressContainer.find('.wpwps-progress-percent');
    var progressCount = progressContainer.find('.wpwps-progress-count');
    var messageContainer = $('#wpwps-products-message');
    var progressInterval;
    
    /**
     * Start import
     */
    importButton.on('click', function() {
        var button = $(this);
        
        if (confirm('Are you sure you want to import products from Printify? This might take a while.')) {
            wpwps.showLoading(button, true);
            clearButton.prop('disabled', true);
            
            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_import_products',
                    nonce: wpwps.nonce
                },
                success: function(response) {
                    if (response.success) {
                        wpwps.showMessage(response.data.message, 'success', messageContainer);
                        
                        // Show progress bar and start monitoring progress
                        progressContainer.show();
                        startProgressMonitor();
                    } else {
                        wpwps.showMessage(response.data.message, 'error', messageContainer);
                        wpwps.showLoading(button, false);
                        clearButton.prop('disabled', false);
                    }
                },
                error: function() {
                    wpwps.showMessage('An error occurred while starting import.', 'error', messageContainer);
                    wpwps.showLoading(button, false);
                    clearButton.prop('disabled', false);
                }
            });
        }
    });
    
    /**
     * Clear all products
     */
    clearButton.on('click', function() {
        var button = $(this);
        
        if (confirm('Are you sure you want to delete all imported Printify products? This action cannot be undone.')) {
            wpwps.showLoading(button, true);
            importButton.prop('disabled', true);
            
            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_clear_products',
                    nonce: wpwps.nonce
                },
                success: function(response) {
                    wpwps.showLoading(button, false);
                    importButton.prop('disabled', false);
                    
                    if (response.success) {
                        wpwps.showMessage(response.data.message, 'success', messageContainer);
                    } else {
                        wpwps.showMessage(response.data.message, 'error', messageContainer);
                    }
                },
                error: function() {
                    wpwps.showLoading(button, false);
                    importButton.prop('disabled', false);
                    wpwps.showMessage('An error occurred while clearing products.', 'error', messageContainer);
                }
            });
        }
    });
    
    /**
     * Monitor import progress
     */
    function startProgressMonitor() {
        // Check progress every 3 seconds
        progressInterval = setInterval(checkProgress, 3000);
        checkProgress();
    }
    
    /**
     * Check import progress
     */
    function checkProgress() {
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_get_import_progress',
                nonce: wpwps.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateProgressUI(response.data);
                    
                    // If import is complete, stop monitoring
                    if (!response.data.in_progress) {
                        stopProgressMonitor();
                    }
                } else {
                    // Error getting progress, stop monitoring
                    stopProgressMonitor();
                }
            },
            error: function() {
                // Error getting progress, stop monitoring
                stopProgressMonitor();
            }
        });
    }
    
    /**
     * Update progress UI
     */
    function updateProgressUI(progress) {
        var percent = progress.percent || 0;
        
        // Update progress bar
        progressBar.css('width', percent + '%');
        progressPercent.text(percent + '%');
        
        // Update count
        progressCount.text(progress.processed + ' / ' + progress.total);
        
        // If complete, show success message
        if (percent >= 100) {
            wpwps.showMessage('Product import completed successfully!', 'success', messageContainer);
        }
    }
    
    /**
     * Stop progress monitor
     */
    function stopProgressMonitor() {
        clearInterval(progressInterval);
        wpwps.showLoading(importButton, false);
        clearButton.prop('disabled', false);
    }
    
    // Check if import is already in progress when page loads
    function checkInitialProgress() {
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_get_import_progress',
                nonce: wpwps.nonce
            },
            success: function(response) {
                if (response.success && response.data.in_progress) {
                    // Import is already in progress
                    progressContainer.show();
                    updateProgressUI(response.data);
                    
                    wpwps.showLoading(importButton, true);
                    clearButton.prop('disabled', true);
                    startProgressMonitor();
                }
            }
        });
    }
    
    // Check initial progress on page load
    checkInitialProgress();
});