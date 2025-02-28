/**
 * JavaScript file: shops.js for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 * Time: 02:14:58
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
            
            // Get the currently selected shop ID (convert to string for consistent comparison)
            const selectedShopId = String(this.$shopInput.val() || '');
            console.log('Current selected shop:', selectedShopId || 'none');
            
            // Display shops in a formatted table
            let html = '<div class="printify-shops-table-wrapper">';
            html += '<h3>Available Printify Shops</h3>';
            html += '<table class="printify-shops-table">';
            html += '<thead><tr><th>Shop Name</th><th>ID</th><th>Action</th></tr></thead>';
            html += '<tbody>';
            
            shops.forEach(shop => {
                // Convert shop.id to string for consistent comparison
                const shopId = String(shop.id);
                const isSelected = selectedShopId === shopId;
                
                html += `<tr class="${isSelected ? 'selected-shop' : ''}">
                    <td>${this.escapeHTML(shop.title)}</td>
                    <td>${this.escapeHTML(shopId)}</td>
                    <td>
                        <button class="button ${isSelected ? 'button-green selected' : 'button-secondary'} select-shop" 
                            data-shop-id="${shopId}" 
                            data-shop-name="${this.escapeHTML(shop.title)}"
                            ${isSelected ? 'disabled' : ''}>
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
            
            // Auto-select first shop if no shop is currently selected
            if (!selectedShopId && shops.length > 0) {
                console.log('No shop selected, selecting first shop automatically');
                this.autoSelectFirstShop(shops[0]);
            }
        },
        
        // Method to handle automatic selection of first shop
        autoSelectFirstShop: function(shop) {
            if (!shop || !shop.id) return;
            
            // Convert shop ID to string to ensure consistent comparison
            const shopId = String(shop.id);
            console.log('Auto-selecting first shop:', shop.title, shopId);
            
            // Update hidden input value
            this.$shopInput.val(shopId);
            
            // IMPORTANT: Allow rendering to complete before updating UI
            setTimeout(() => {
                // Force UI update after a short delay to ensure DOM is ready
                this.updateSelectedShopUI(shopId, shop.title);
                
                // Save to database
                this.saveSelectedShop(shopId, shop.title, true);
            }, 100);
        },
        
        // Updated UI helper with improved selectors and green disabled buttons
        updateSelectedShopUI: function(shopId, shopName) {
            console.log('Updating UI for selected shop:', shopName, shopId);
            
            // More specific debugging to check if buttons exist
            const allButtons = $('.printify-shops-table .select-shop').length;
            const targetButton = $(`.printify-shops-table .select-shop[data-shop-id="${shopId}"]`).length;
            console.log(`Found ${allButtons} total buttons, ${targetButton} matching shop ID ${shopId}`);
            
            // Reset all buttons first - enable and set to secondary style
            $('.printify-shops-table .select-shop')
                .removeClass('button-green selected button-primary')
                .addClass('button-secondary')
                .prop('disabled', false)
                .text('Select');
                
            // Get the specific button by shop ID
            const $button = $(`.printify-shops-table .select-shop[data-shop-id="${shopId}"]`);
            
            if ($button.length) {
                // Update button state - apply green style and disable
                $button
                    .removeClass('button-secondary')
                    .addClass('button-green selected')
                    .prop('disabled', true)
                    .text('Selected');
                
                // Update row highlighting
                $('.printify-shops-table tr').removeClass('selected-shop');
                $button.closest('tr').addClass('selected-shop');
                
                console.log('Button UI updated successfully');
            } else {
                console.error('Failed to find button for shop ID:', shopId);
            }
        },
        
        selectShop: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const shopId = String($button.data('shop-id'));
            const shopName = $button.data('shop-name');
            
            console.log('Shop selected manually:', shopName, shopId);
            
            // Update the hidden input with the selected shop ID
            this.$shopInput.val(shopId);
            
            // Update UI
            this.updateSelectedShopUI(shopId, shopName);
            
            // Save the selection via AJAX
            this.saveSelectedShop(shopId, shopName, false);
        },
        
        saveSelectedShop: function(shopId, shopName, isAutoSelected) {
            console.log('Saving shop selection to database:', shopId, isAutoSelected ? '(auto-selected)' : '');
            
            $.ajax({
                url: PrintifySync.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_selected_shop',
                    nonce: PrintifySync.nonce,
                    shop_id: shopId,
                    auto_selected: isAutoSelected ? 1 : 0
                },
                success: (response) => {
                    console.log('Save shop response:', response);
                    
                    if (response.success) {
                        console.log('Shop saved successfully, ID:', response.data.shop_id);
                        
                        // Update UI again after AJAX to be sure
                        this.updateSelectedShopUI(shopId, shopName);
                        
                        // Show a success message for manual selections only
                        if (!isAutoSelected) {
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
                        }
                        
                        // Trigger custom event that products.js can listen for
                        $(document).trigger('printify:shop_selected', [response.data.shop_id]);
                        
                        // Force refresh the products section if it exists
                        if ($('#printify-products-results').length > 0) {
                            $('#fetch-printify-products').prop('disabled', false);
                        }
                    } else {
                        console.error('Error saving shop selection:', response.data.message);
                        if (!isAutoSelected) {
                            this.showError(`Failed to save shop selection: ${response.data.message}`);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error saving shop selection:', error);
                    if (!isAutoSelected) {
                        this.showError(`AJAX error: ${error}`);
                    }
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
