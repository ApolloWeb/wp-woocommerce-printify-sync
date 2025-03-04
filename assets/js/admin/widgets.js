/**
 * WooCommerce Printify Sync - Admin Widgets JavaScript
 * 
 * Handles widget functionality and layout
 */

(function($) {
    'use strict';
    
    var PrintifyWidgets = {
        
        init: function() {
            this.setupWidgetCollapse();
            this.setupWidgetRefresh();
            this.setupWidgetDrag();
            this.setupDataTables();
            this.initializeCharts();
        },
        
        // Allow widgets to be collapsed
        setupWidgetCollapse: function() {
            $('.widget-header .widget-toggle').on('click', function(e) {
                e.preventDefault();
                var $widget = $(this).closest('.dashboard-widget');
                var $content = $widget.find('.widget-content');
                var $icon = $(this).find('i');
                
                $content.slideToggle(300, function() {
                    if ($content.is(':visible')) {
                        $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                        $widget.removeClass('collapsed');
                    } else {
                        $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                        $widget.addClass('collapsed');
                    }
                    
                    // Save state to user preferences
                    saveWidgetState($widget.data('widget-id'), $content.is(':visible') ? 'expanded' : 'collapsed');
                });
            });
            
            // Check saved states and apply
            $('.dashboard-widget').each(function() {
                var widgetId = $(this).data('widget-id');
                var savedState = getUserWidgetState(widgetId);
                
                if (savedState === 'collapsed') {
                    var $widget = $(this);
                    var $content = $widget.find('.widget-content');
                    var $icon = $widget.find('.widget-toggle i');
                    
                    $content.hide();
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    $widget.addClass('collapsed');
                }
            });
            
            // Helper function to save widget state
            function saveWidgetState(widgetId, state) {
                if (typeof widgetId !== 'undefined') {
                    // Use AJAX to save state
                    $.ajax({
                        url: printifySyncAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'save_widget_state',
                            widget_id: widgetId,
                            state: state,
                            nonce: printifySyncAjax.n