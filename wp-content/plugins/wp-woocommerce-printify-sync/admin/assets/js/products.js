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
        },

        cacheDOM: function() {
            this.$fetchButton = $('#fetch-printify-products');
            console.log('Products button found:', this.$fetchButton.length > 0);
            this.$resultsContainer = $('#printify-products-results');
            this.$shopSelect = $('select[name="printify_selected_shop"]');
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
        },

        fetchProducts: function() {
            console.log('fetchProducts function called');
            
            // Check if API key and shop are selected
            if (!this.$apiKeyField.val().trim()) {
                this.showError('Please enter your Printify API key first');
                return;
            }
            
            if (!this.$shopSelect.val()) {
                this.showError('Please select a shop first');
                return;
            }
            
            // Show loading indicator
            this.$resultsContainer.html('<div class="printify-loading">Loading products...</div>');
            
            // Make AJAX request
            $.ajax({
                url: PrintifySync.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_printify_products',
                    nonce: PrintifySync.nonce
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
                const defaultImage = '//via.placeholder.com/150x150?text=No+Image';
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
            
            // Attach event handlers to import buttons (for future implementation)
            $('.import-product').on('click', this.importProduct.bind(this));
        },
        
        importProduct: function(e) {
            const productId = $(e.target).data('product-id');
            console.log('Import requested for product ID:', productId);
            alert('Product import functionality will be implemented in a future version.');
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