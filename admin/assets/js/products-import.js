/**
 * WP WooCommerce Printify Sync - Products Import Functionality
 * 
 * @package WP WooCommerce Printify Sync
 * @version 1.0.0
 * @date 2025-03-01 09:08:22
 * @user ApolloWeb
 */

(function($) {
    'use strict';
    
    const ProductsImport = {
        // Initialize products import functionality
        init: function() {
            // Skip if not on products page
            if (!$('#wpwps-import-products').length) {
                return;
            }
            
            // Elements
            this.importButton = $('#wpwps-import-products');
            this.clearButton = $('#wpwps-clear-products');
            this.progressContainer = $('#wpwps-import-progress');
            this.progressBar = $('#wpwps-progress-bar');
            this.progressPercent = $('#wpwps-progress-percent');
            this.progressCount = $('#wpwps-progress-count');
            this.processedCounter = $('#wpwps-processed');
            this.createdCounter = $('#wpwps-created');
            this.updatedCounter = $('#wpwps-updated');
            this.skippedCounter = $('#wpwps-skipped');
            this.messageContainer = $('#wpwps-products-message');
            
            // State
            this.isImporting = false;
            
            this.bindEvents();
        },
        
        // Bind event handlers
        bindEvents: function() {
            const self = this;
            
            this.importButton.on('click', function(e) {
                e.preventDefault();
                if (!self.isImporting && confirm(wpwps_i18n.confirm_import)) {
                    self.startImport();
                }
            });
            
            this.clearButton.on('click', function(e) {
                e.preventDefault();
                if (confirm(wpwps_i18n.confirm_clear)) {
                    self.clearProducts();
                }
            });
        },
        
        // Start the import process
        startImport: function() {
            const self = this;
            this.isImporting = true;
            
            // Reset progress indicators
            this.progressBar.css('width', '0%');
            this.progressPercent.text('0%');
            this.progressCount.text('0 / 0');
            this.processedCounter.text('0');
            this.createdCounter.text('0');
            this.updatedCounter.text('0');
            this.skippedCounter.text('0');
            
            // Show progress container
            this.progressContainer.show();
            
            // Update button state
            this.importButton.prop('disabled', true)
                .find('.wpwps-spinner').show();
            this.importButton.find('.wpwps-button-icon').hide();
            
            // Clear any previous messages
            this.showMessage('');
            
            // Make AJAX request to start import
            $.ajax({
                url: wpwps_ajax.url,
                type: 'POST',
                data: {
                    action: 'wpwps_import_products',
                    nonce: wpwps_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateProgress(0, response.data.total);
                        self.pollImportProgress();
                    } else {
                        self.showMessage(response.data.message || 'Error starting import', 'error');
                        self.resetImportButton();
                    }
                },
                error: function(xhr, status, error) {
                    self.showMessage('AJAX error: ' + error, 'error');
                    self.resetImportButton();
                }
            });
        },
        
        // Poll for import progress
        pollImportProgress: function() {
            const self = this;
            
            $.ajax({
                url: wpwps_ajax.url,
                type: 'POST',
                data: {
                    action: 'wpwps_get_import_progress',
                    nonce: wpwps_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Update progress indicators
                        self.updateProgress(
                            data.processed,
                            data.total,
                            data.created,
                            data.updated,
                            data.skipped
                        );
                        
                        // Continue polling or finish
                        if (data.status === 'running') {
                            setTimeout(function() {
                                self.pollImportProgress();
                            }, 1000);
                        } else if (data.status === 'complete') {
                            self.importComplete(data);
                        } else {
                            self.showMessage('Import stopped unexpectedly', 'warning');
                            self.resetImportButton();
                        }
                    } else {
                        self.showMessage(response.data.message || 'Error checking import progress', 'error');
                        self.resetImportButton();
                    }
                },
                error: function(xhr, status, error) {
                    self.showMessage('AJAX error: ' + error, 'error');
                    self.resetImportButton();
                }
            });
        },
        
        // Update progress indicators
        updateProgress: function(processed, total, created, updated, skipped) {
            const percent = total > 0 ? Math.round((processed / total) * 100) : 0;
            
            this.progressBar.css('width', percent + '%');
            this.progressPercent.text(percent + '%');
            this.progressCount.text(processed + ' / ' + total);
            this.processedCounter.text(processed || 0);
            this.createdCounter.text(created || 0);
            this.updatedCounter.text(updated || 0);
            this.skippedCounter.text(skipped || 0);
        },
        
        // Handle import completion
        importComplete: function(data) {
            // Reload product list
            window.location.reload();
        },
        
        // Reset import button to default state
        resetImportButton: function() {
            this.importButton.prop('disabled', false)
                .find('.wpwps-spinner').hide();
            this.importButton.find('.wpwps-button-icon').show();
            this.isImporting = false;
        },
        
        // Clear all products
        clearProducts: function() {
            const self = this;
            
            // Update button state
            this.clearButton.prop('disabled', true)
                .find('.wpwps-spinner').show();
            this.clearButton.find('.wpwps-button-icon').hide();
            
            $.ajax({
                url: wpwps_ajax.url,
                type: 'POST',
                data: {
                    action: 'wpwps_clear_products',
                    nonce: wpwps_ajax.nonce
                },
                success: function(response) {
                    // Reset button state
                    self.clearButton.prop('disabled', false)
                        .find('.wpwps-spinner').hide();
                    self.clearButton.find('.wpwps-button-icon').show();
                    
                    if (response.success) {
                        // On success, reload the page to show empty product list
                        window.location.reload();
                    } else {
                        self.showMessage(response.data.message || 'Error clearing products', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    // Reset button state
                    self.clearButton.prop('disabled', false)
                        .find('.wpwps-spinner').hide();
                    self.clearButton.find('.wpwps-button-icon').show();
                    
                    self.showMessage('AJAX error: ' + error, 'error');
                }
            });
        },
        
        // Display a message
        showMessage: function(message, type) {
            const container = this.messageContainer;
            
            if (!message) {
                container.html('').hide();
                return;
            }
            
            const messageClass = type ? 'wpwps-message-' + type : 'wpwps-message-info';
            
            // Create message HTML
            const messageHtml = `
                <div class="wpwps-message ${messageClass}">
                    <div class="wpwps-message-icon">
                        ${this.getMessageIcon(type)}
                    </div>
                    <div class="wpwps-message-content">
                        <div class="wpwps-message-body">${message}</div>
                    </div>
                    <button type="button" class="wpwps-message-close">&times;</button>
                </div>
            `;
            
            container.html(messageHtml).show();
            
            // Auto-hide success messages after delay
            if (type === 'success') {
                setTimeout(function() {
                    container.find('.wpwps-message').fadeOut();
                }, 5000);
            }
        },
        
        // Get appropriate icon for message type
        getMessageIcon: function(type) {
            switch(type) {
                case 'success':
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
                case 'error':
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
                case 'warning':
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
                default:
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        ProductsImport.init();
    });
    
})(jQuery);