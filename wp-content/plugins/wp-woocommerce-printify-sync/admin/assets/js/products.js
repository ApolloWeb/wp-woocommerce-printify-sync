/**
 * Printify Products Management
 */
(function($) {
    'use strict';

    console.log('Printify Sync: Products script loaded');

    const PrintifyProducts = {
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
                
                // Update button text to include shop ID
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
            
            // Double-check if shop ID exists
            if (!shopId) {
                this.showError('No shop selected. Please select a shop first.');
                return;
            }
            
            // Show loading indicator
            this.$resultsContainer.html('<div class="printify-loading">Loading products from shop ID: ' + shopId + '...</div>');
            
            // Make AJAX request
            $.ajax({
                url: PrintifySync.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_printify_products',
                    nonce: PrintifySync.nonce,
                    shop_id: shopId // Pass the shop_id explicitly for clarity
                },
                success: this.handleSuccess.bind(this),
                error: this.handleError.bind(this)
            });
        },

        handleSuccess: function(response) {
            console.log('Products AJAX response received');
            
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
            
            // Display products in a formatted grid
            let html = '<div class="printify-products-wrapper">';
            html += '<h3>Available Products in Selected Shop</h3>';
            html += '<div class="printify-products-grid">';
            
            products.forEach(product => {
                const images = product.images || [];
                const firstImage = images.length > 0 ? images[0].src : '';
                const defaultImage = '//via.placeholder.com/300x300?text=No+Image';
                const status = product.visible ? 'Published' : 'Draft';
                const statusClass = product.visible ? 'published' : 'draft';
                
                html += `
                <div class="printify-product-card">
                    <div class="printify-product-image">
                        <img src="${firstImage || defaultImage}" alt="${this.escapeHTML(product.title)}">
                    </div>
                    <div class="printify-product-details">
                        <h4>${this.escapeHTML(product.title)}</h4>
                        <p class="product-description">${this.truncateText(product.description || 'No description', 100)}</p>
                        <div class="product-meta">
                            <span class="product-id">ID: ${product.id}</span>
                            <span class="product-status status-${statusClass}">${status}</span>
                        </div>
                        <div class="product-actions">
                            <button class="button import-product" data-product-id="${product.id}">Import to WooCommerce</button>
                        </div>
                    </div>
                </div>`;
            });
            
            html += '</div></div>';
            
            // Show the grid
            this.$resultsContainer.html(html);
            
            // Attach event handlers to import buttons
            $('.import-product').on('click', this.importProduct.bind(this));
        },
        
        importProduct: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const productId = $button.data('product-id');
            
            console.log('Import requested for product ID:', productId);
            
            // Disable the button and show loading state
            $button.prop('disabled', true).text('Importing...');
            
            // Here you would make an AJAX request to import the product
            // For now, just show a placeholder message
            setTimeout(() => {
                $button.text('Import Coming Soon');
                
                // Show placeholder message
                $button.closest('.printify-product-details').append(
                    '<div class="printify-notice printify-notice-info" style="margin-top: 10px;">' +
                    'Product import functionality will be implemented in a future version.' +
                    '</div>'
                );
            }, 1000);
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
        },
        
        truncateText: function(text, maxLength) {
            if (!text) return '';
            if (text.length <= maxLength) return text;
            return text.substr(0, maxLength) + '...';
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