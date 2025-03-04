/**
<<<<<<< HEAD
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
=======
 * Dashboard JavaScript - Chart Rendering and Dashboard Functionality * Version: 1.0.7
 * Date: 2025-03-03
 */
jQuery(function($)

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: jQuery(function($)
#
#
# Commit Hash 16c804f
#
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
