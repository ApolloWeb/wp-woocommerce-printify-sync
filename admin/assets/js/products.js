/**
 * JavaScript file: products.js for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 * Time: 02:14:58
 */
(function($) {
    'use strict';

    const PrintifyProducts = {
        cachedProducts: null,

        init: function() {
            this.cacheDOM();
            this.bindEvents();
        },

        cacheDOM: function() {
            this.$fetchButton = $('#fetch-printify-products');
            this.$resultsContainer = $('#printify-products-results');
            this.$shopInput = $('#printify_selected_shop');
        },

        bindEvents: function() {
            this.$fetchButton.on('click', (e) => {
                e.preventDefault();
                this.fetchProducts();
            });
        },

        fetchProducts: function() {
            const shopId = this.$shopInput.val();

            if (!shopId) {
                this.showError('No shop selected.');
                return;
            }

            this.$resultsContainer.html('<div class="loading">Fetching products...</div>');

            $.ajax({
                url: PrintifySync.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_printify_products',
                    nonce: PrintifySync.nonce,
                    shop_id: shopId
                },
                success: (response) => {
                    if (!response.success) {
                        this.showError(response.data.message || 'Error fetching products');
                        return;
                    }

                    this.cachedProducts = response.data;
                    this.displayProductsSummary();
                },
                error: () => {
                    this.showError('AJAX error occurred.');
                }
            });
        },

        displayProductsSummary: function() {
            let html = `<h3>Products Found (${this.cachedProducts.length})</h3>`;
            html += '<ul>';
            this.cachedProducts.forEach(product => {
                html += `<li>${product.title}</li>`;
            });
            html += '</ul>';
            this.$resultsContainer.html(html);
        },

        showError: function(message) {
            this.$resultsContainer.html(`<div class="error">${message}</div>`);
        }
    };

    $(document).ready(function() {
        PrintifyProducts.init();
    });

})(jQuery);
