/**
 * Printify Shops Management
 */
(function($) {
    'use strict';

    console.log('Printify Sync: Shops script loaded');

    const PrintifyShops = {
        init: function() {
            console.log('Initializing PrintifyShops module');
            this.cacheDOM();
            
            // Auto-fetch shops on page load
            this.fetchShops();
        },

        cacheDOM: function() {
            this.$resultsContainer = $('#printify-shops-results');
            this.$shopInput = $('#printify_selected_shop');
            this.$apiKeyField = $('input[name="printify_api_key"]');
        },

        fetchShops: function() {
            console.log('Auto-fetching shops');
            
            // Check if API key is present
            if (!this.$apiKeyField.val().trim()) {
                this.showError('Please enter your Printify API key first');
                return;
            }
            
            // Show loading indicator
            this.$resultsContainer.html('<div class="printify-loading">Loading shops...</div>');
            
            // Make AJAX request
            $.ajax({
                url: PrintifySync.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_printify_shops',
                    nonce: PrintifySync.nonce
                },
                success: this.handleSuccess.bind(this),
                error: this.handleError.bind(this)
            });
        },

        handleSuccess: function(response) {
            if (!response.success) {
                this.showError(response.data.message || 'Error fetching shops');
                return;
            }
            
            const shops = response.data;
            
            if (!shops || shops.length === 0) {
                this.showError('No shops found for this account');
                return;
            }
            
            const selectedShopId = this.$shopInput.val();
            
            // Display shops in a formatted table
            let html = '<div class="printify-shops-table-wrapper">';
            html += '<h3>Available Printify Shops</h3>';
            html += '<table class="printify-shops-table">';
            html += '<thead><tr><th>Shop Name</th><th>ID</th><th>Action</th></tr></thead>';
            html += '<tbody>';
            
            shops.forEach(shop => {
                const isSelected = selectedShopId === shop.id;
                html += `<tr class="${isSelected ? 'selected-shop' : ''}">
                    <td>${this.escapeHTML(shop.title)}</td>
                    <td>${this.escapeHTML(shop.id)}</td>
                    <td>
                        <button class="button ${isSelected ? 'button-primary selected' : 'button-secondary'} select-shop" 
                            data-shop-id="${shop.id}" 
                            data-shop-name="${this.escapeHTML(shop.title)}">
                            ${isSelected ? 'Selected' : 'Select'}
                        </button>
                    </td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            
            // Show the table
            this.$resultsContainer.html(html);
            
            // Attach event handlers to select buttons
            $('.select-shop').on('click', this.selectShop.bind(this));
            
            // Auto-select first shop if nothing is selected
            if (!selectedShopId && shops.length > 0) {
                console.log('No shop selected, auto-selecting first shop');
                // Simulate click on first shop's select button
                setTimeout(() => {
                    $('.select-shop:first').trigger('click');
                }, 500);
            }
        },
        
        selectShop: function(e) {
            if (e) e.preventDefault();
            const $button = $(e.currentTarget);
            const shopId = $button.data('shop-id');
            const shopName = $button.data('shop-name');
            
            console.log('Shop selected:', shopName, shopId);
            
            // Update the hidden input with the selected shop ID
            this.$shopInput.val(shopId);
            
            // Update all buttons to show only the selected one as primary
            $('.select-shop').removeClass('button-primary selected').addClass('button-secondary').text('Select');
            $button.removeClass('button-secondary').addClass('button-primary selected').text('Selected');
            
            // Highlight the selected row
            $('.printify-shops-table tr').removeClass('selected-shop');
            $button.closest('tr').addClass('selected-shop');
            
            // Save the selection via AJAX
            this.saveSelectedShop(shopId, shopName);
        },
        
        saveSelectedShop: function(shopId, shopName) {
            $.ajax({
                url: PrintifySync.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_selected_shop',
                    nonce: PrintifySync.nonce,
                    shop_id: shopId
                },
                success: function(response) {
                    if (response.success) {
                        // Show a success message
                        $('.printify-shops-table-wrapper').prepend(
                            `<div class="printify-notice printify-notice-success">
                                Shop "${shopName}" selected successfully.
                            </div>`
                        );
                        
                        // Remove the message after 3 seconds
                        setTimeout(function() {
                            $('.printify-notice-success').fadeOut('slow', function() {
                                $(this).remove();
                            });
                        }, 3000);
                    } else {
                        console.error('Error saving shop selection:', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error saving shop selection:', error);
                }
            });
        },

        handleError: function(xhr, status, error) {
            console.error('Shops AJAX error:', error);
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
            console.log('Settings page detected, initializing shops module');
            PrintifyShops.init();
        } else {
            console.log('Settings page not detected, skipping shops module initialization');
        }
    });

})(jQuery);