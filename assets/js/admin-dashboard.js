/**
 * Admin Dashboard JavaScript
 */

jQuery(document).ready(function($) {
    // Initialize dashboard widgets
    function initDashboard() {
        loadShopInfo();
        loadProductSyncSummary();
        loadOrdersOverview();
        loadWebhookStatus();
    }
    
    // Load shop information widget data
    function loadShopInfo() {
        var $widget = $('#shop-info-widget');
        
        if (!$widget.length) {
            return;
        }
        
        $widget.html('<div class="widget-loading"><span class="spinner is-active"></span></div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'printify_get_shop_info',
                nonce: printify_dashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    $widget.html(response.data.html);
                } else {
                    $widget.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $widget.html('<div class="notice notice-error"><p>Failed to load shop information.</p></div>');
            }
        });
    }
    
    // Load product sync summary widget data
    function loadProductSyncSummary() {
        var $widget = $('#product-sync-widget');
        
        if (!$widget.length) {
            return;
        }
        
        $widget.html('<div class="widget-loading"><span class="spinner is-active"></span></div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'printify_get_product_sync_summary',
                n