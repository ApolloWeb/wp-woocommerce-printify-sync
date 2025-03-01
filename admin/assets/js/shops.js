/**
 * Shops page JavaScript for WP WooCommerce Printify Sync
 */
jQuery(document).ready(function($) {
    'use strict';
    
    var shopsTable = $('#wpwps-shops-list');
    var loadingEl = $('#wpwps-shops-loading');
    var errorEl = $('#wpwps-shops-error');
    var rowTemplate = $('#wpwps-shop-row-template').html();
    var selectedShopId = parseInt($('.wpwps-shops').data('selected-shop') || 0);
    
    /**
     * Load shops from API
     */
    function loadShops() {
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_get_shops',
                nonce: wpwps.nonce
            },
            success: function(response) {
                loadingEl.hide();
                
                if (response.success) {
                    renderShops(response.data.shops);
                } else {
                    errorEl.html('<p>' + response.data.message + '</p>').show();
                }
            },
            error: function() {
                loadingEl.hide();
                errorEl.html('<p>Failed to load shops. Please check your API settings.</p>').show();
            }
        });
    }
    
    /**
     * Render shops in the table
     */
    function renderShops(shops) {
        if (!shops || !shops.length) {
            errorEl.html('<p>No shops found. Please check your Printify account.</p>').show();
            return;
        }
        
        var tbody = shopsTable.find('tbody');
        tbody.empty();
        
        $.each(shops, function(i, shop) {
            var isSelected = (shop.id === selectedShopId);
            var html = rowTemplate
                .replace(/{id}/g, shop.id)
                .replace(/{title}/g, shop.title)
                .replace(/{status}/g, shop.is_active ? 'Active' : 'Inactive')
                .replace(/{selected}/g, isSelected ? 'selected' : '');
            
            tbody.append(html);
        });
        
        shopsTable.show();
    }
    
    /**
     * Select a shop
     */
    $(document).on('click', '.wpwps-select-shop', function() {
        var button = $(this);
        var shopId = button.data('shop-id');
        
        wpwps.showLoading(button, true);
        
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_select_shop',
                nonce: wpwps.nonce,
                shop_id: shopId
            },
            success: function(response) {
                if (response.success) {
                    // Update UI to show selected shop
                    shopsTable.find('tr').removeClass('selected');
                    shopsTable.find('tr[data-shop-id="' + shopId + '"]').addClass('selected');
                    
                    // Show success message
                    wpwps.showMessage(response.data.message, 'success', errorEl);
                    selectedShopId = shopId;
                } else {
                    wpwps.showMessage(response.data.message, 'error', errorEl);
                }
            },
            error: function() {
                wpwps.showMessage('An error occurred while selecting shop.', 'error', errorEl);
            },
            complete: function() {
                wpwps.showLoading(button, false);
            }
        });
    });
    
    // Load shops on page load
    loadShops();
});