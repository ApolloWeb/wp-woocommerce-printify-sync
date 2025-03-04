/**
 * Admin Dashboard JavaScript
 * 
 * Handles loading and rendering of dashboard widgets and charts.
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
                $widget.html('<div class="notice notice-error"><p>Failed to load product sync summary.</p></div>');
            }
        });
    }

    // Load orders overview widget data
    function loadOrdersOverview() {
        var $widget = $('#orders-overview-widget');
        
        if (!$widget.length) {
            return;
        }
        
        $widget.html('<div class="widget-loading"><span class="spinner is-active"></span></div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'printify_get_orders_overview',
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
                $widget.html('<div class="notice notice-error"><p>Failed to load orders overview.</p></div>');
            }
        });
    }

    // Load webhook status widget data
    function loadWebhookStatus() {
        var $widget = $('#webhook-status-widget');
        
        if (!$widget.length) {
            return;
        }
        
        $widget.html('<div class="widget-loading"><span class="spinner is-active"></span></div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'printify_get_webhook_status',
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
                $widget.html('<div class="notice notice-error"><p>Failed to load webhook status.</p></div>');
            }
        });
    }

    // Initialize dashboard
    initDashboard();
});