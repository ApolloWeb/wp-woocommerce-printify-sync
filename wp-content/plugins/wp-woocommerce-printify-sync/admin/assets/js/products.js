/**
 * Printify Products Management
 */
(function($) {
    'use strict';

    console.log('Printify Sync: Products script loaded');

    const PrintifyProducts = {
        // Add property to store shop ID across methods
        currentShopId: null,
        importQueue: [],
        totalProducts: 0,
        importedCount: 0,

        init: function() {
            console.log('Initializing PrintifyProducts module');
            this.cacheDOM();
            this.bindEvents();
            
            // Check initial shop selection status
            this.checkShopSelection();
        },

        cacheDOM: function() {
            this.$fetchButton = $('#fetch-printify-products');
            console.log('Products button found:', this.$fetchButton.length > 0);
            this.$resultsContainer = $('#printify-products-results');
            this.$shopInput = $('#printify_selected_shop');
            this.$apiKeyField = $('input[name="printify_api_key"]');
        },

        bindEvents: function() {
            console.log('Binding click event to products fetch button');
            // Direct handling to avoid binding issues
            this.$fetchButton.on('click', (e) => {
                console.log('Products button clicked!');
                e.preventDefault();
                this.fetchProducts();
            });
            
            // Monitor shop selection changes
            $(document).on('printify:shop_selected', (e, shopId) => {
                console.log('Shop selection changed event received:', shopId);
                this.checkShopSelection();
            });
        },
        
        checkShopSelection: function() {
            // Get the current shop ID from hidden input
            const shopId = this.$shopInput.val();
            console.log('Current shop ID for products:', shopId || 'none');
            
            if (shopId) {
                // Enable button if shop is selected
                this.$fetchButton.prop('disabled', false);
                
                // Update button text
                this.$fetchButton.text('Fetch Products from Shop');
            } else {
                // Disable button if no shop is selected
                this.$fetchButton.prop('disabled', true);
                this.$resultsContainer.html(
                    '<div class="printify-notice printify-notice-info">' + 
                    'Please select a Printify shop first from the Shops section above.' + 
                    '</div>'
                );
            }
        },

        fetchProducts: function() {
            console.log('fetchProducts function called');
            const shopId = this.$shopInput.val();
            
            // Store the shop ID for use in other methods
            this.currentShopId = shopId;
            
            // Double-check if shop ID exists
            if (!shopId) {
                this.showError('No shop selected. Please select a shop first.');
                return;
            }
            
            // Show loading indicator with progress bar
            this.$resultsContainer.html(`
                <div class="printify-loading-container">
                    <div class="printify-progress-title">Fetching products from Shop ID: ${shopId}...</div>
                    <div class="printify-progress-bar-container">
                        <div class="printify-progress-bar" style="width: 5%;"></div>
                    </div>
                    <div class="printify-progress-counter">Initializing...</div>
                </div>
            `);
            
            // Set progress to "connecting" state
            setTimeout(() => {
                $('.printify-progress-bar').css('width', '20%');
                $('.printify-progress-counter').text('Connecting to Printify API...');
            }, 300);
            
            // Make AJAX request
            $.ajax({
                url: PrintifySync.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_printify_products',
                    nonce: PrintifySync.nonce,
                    shop_id: shopId
                },
                success: this.handleSuccess.bind(this),
                error: this.handleError.bind(this)
            });
        },

        handleSuccess: function(response) {
            console.log('Products AJAX response received');
            
            // Update progress
            $('.printify-progress-bar').css('width', '80%');
            $('.printify-progress-counter').text('Processing response...');
            
            if (!response.success) {
                this.showError(response.data.message || 'Error fetching products');
                return;
            }
            
            const products = response.data;
            console.log('Products found:', products.length);
            
            if (!products || products.length === 0) {
                this.showError('No products found in this shop');
                return;
            }
            
            // Complete the progress bar
            $('.printify-progress-bar').css('width', '100%');
            
            // Short pause before showing results
            setTimeout(() => {
                // Display summary instead of individual products
                let html = '<div class="printify-products-summary">';
                html += `<h3>Products Found in Shop</h3>`;
                
                // Add product count with icon
                html += `
                    <div class="printify-summary-box">
                        <div class="summary-icon">📦</div>
                        <div class="summary-count">${products.length}</div>
                        <div class="summary-label">Products Found</div>
                    </div>
                `;
                
                // Add status breakdown
                const publishedProducts = products.filter(p => p.visible).length;
                const draftProducts = products.length - publishedProducts;
                
                html += `
                    <div class="printify-summary-stats">
                        <div class="stat-item">
                            <span class="stat-label">Published:</span>
                            <span class="stat-value">${publishedProducts}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Draft:</span>
                            <span class="stat-value">${draftProducts}</span>
                        </div>
                    </div>
                `;
                
                // Add import all button
                html += `
                    <div class="printify-summary-actions">
                        <button class="button button-primary import-all-products" data-shop-id="${this.currentShopId}">
                            Import All Products to WooCommerce
                        </button>
                    </div>
                </div>`;
                
                // Show the summary
                this.$resultsContainer.html(html);
                
                // Store products for later use
                this.importQueue = products.map(p => p.id);
                this.totalProducts = products.length;
                
                // Attach event handler to import all button
                $('.import-all-products').on('click', this.importAllProducts.bind(this));
            }, 500);
        },
        
        importAllProducts: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const shopId = $button.data('shop-id');
            
            console.log('Import all products requested for shop ID:', shopId);
            
            // Disable the button
            $button.prop('disabled', true).text('Importing...');
            
            // Reset counter
            this.importedCount = 0;
            
            // Display progress container
            this.$resultsContainer.html(`
                <div class="printify-import-container">
                    <h3>Importing Products to WooCommerce</h3>
                    <div class="printify-progress-bar-container">
                        <div class="printify-progress-bar" style="width: 0%;"></div>
                    </div>
                    <div class="printify-progress-counter">
                        <span class="count-imported">0</span>
                        of 
                        <span class="count-total">${this.totalProducts}</span> 
                        products imported
                    </div>
                    <div class="printify-import-log"></div>
                </div>
            `);
            
            // Start the import process
            this.processNextProduct(shopId);
        },
        
        processNextProduct: function(shopId) {
            // Check if we have more products to process
            if (this.importQueue.length === 0) {
                // We're done
                $('.printify-progress-counter').html(`
                    <strong>Import Complete!</strong> ${this.importedCount} products imported.
                `);
                
                // Add message about webhook sync
                $('.printify-import-container').append(`
                    <div class="printify-notice printify-notice-success" style="margin-top: 15px;">
                        <p>Initial product import complete. Future updates will be automatically synchronized via webhooks.</p>
                    </div>
                `);
                
                return;
            }
            
            // Get the next product ID
            const productId = this.importQueue.shift();
            
            // Update log
            $('.printify-import-log').prepend(`
                <div class="import-log-item processing">
                    <span class="log-icon">⏳</span>
                    <span class="log-message">Importing product ID: ${productId}...</span>
                </div>
            `);
            
            // Make AJAX request to import the product
            $.ajax({
                url: PrintifySync.ajax_url,
                type: 'POST',
                data: {
                    action: 'import_printify_product',
                    nonce: PrintifySync.nonce,
                    shop_id: shopId,
                    product_id: productId
                },
                success: (response) => {
                    // Increment counter
                    this.importedCount++;
                    
                    // Update progress bar
                    const progressPercent = Math.round((this.importedCount / this.totalProducts) * 100);
                    $('.printify-progress-bar').css('width', `${progressPercent}%`);
                    $('.count-imported').text(this.importedCount);
                    
                    // Update log item
                    const $logItem = $('.import-log-item.processing').first();
                    $logItem.removeClass('processing');
                    
                    if (response.success) {
                        // Success
                        $logItem.addClass('success');
                        $logItem.find('.log-icon').text('✅');
                        $logItem.find('.log-message').html(`
                            Product "${response.data.title}" imported successfully. 
                            <a href="${response.data.edit_url}" target="_blank">Edit in WooCommerce</a>
                        `);
                    } else {
                        // Error
                        $logItem.addClass('error');
                        $logItem.find('.log-icon').text('❌');
                        $logItem.find('.log-message').text(`Error: ${response.data.message || 'Unknown error'}`);
                    }
                    
                    // Process next product
                    this.processNextProduct(shopId);
                },
                error: (xhr, status, error) => {
                    // Update log item
                    const $logItem = $('.import-log-item.processing').first();
                    $logItem.removeClass('processing').addClass('error');
                    $logItem.find('.log-icon').text('❌');
                    $logItem.find('.log-message').text(`AJAX Error: ${error}`);
                    
                    // Increment counter even on error
                    this.importedCount++;
                    
                    // Update progress
                    const progressPercent = Math.round((this.importedCount / this.totalProducts) * 100);
                    $('.printify-progress-bar').css('width', `${progressPercent}%`);
                    $('.count-imported').text(this.importedCount);
                    
                    // Process next product
                    this.processNextProduct(shopId);
                }
            });
        },

        handleError: function(xhr, status, error) {
            console.error('Products AJAX error:', error);
            this.showError(`AJAX error: ${error}`);
        },

        showError: function(message) {
            this.$resultsContainer.html(
                `<div class="printify-notice printify-notice-error">${message}</div>`
            );
        },
        
        escapeHTML: function(str) {
            if (!str) return '';
            return str.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    };

    $(document).ready(function() {
        // Only initialize on the settings page
        if ($('.printify-sync-settings').length > 0) {
            console.log('Settings page detected, initializing products module');
            PrintifyProducts.init();
        } else {
            console.log('Settings page not detected, skipping products module initialization');
        }
    });

})(jQuery);