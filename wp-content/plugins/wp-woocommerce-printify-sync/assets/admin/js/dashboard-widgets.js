/**
 * Dashboard Widgets JavaScript
 */
(function($) {
    'use strict';
    
    const WPWPS_Dashboard = {
        init: function() {
            this.setupSyncWidget();
            this.setupQueueWidget();
            this.setupRefreshButtons();
            this.setupProcessQueue();
        },
        
        setupSyncWidget: function() {
            const widget = $('.wpwps-sync-stats');
            
            if (widget.length === 0) {
                return;
            }
            
            this.loadSyncStats(widget);
        },
        
        setupQueueWidget: function() {
            const widget = $('.wpwps-queue-stats');
            
            if (widget.length === 0) {
                return;
            }
            
            this.loadQueueStats(widget);
        },
        
        setupRefreshButtons: function() {
            $('.wpwps-refresh-widget').on('click', function() {
                const widget = $(this).closest('.wpwps-dashboard-widget');
                
                if (widget.hasClass('wpwps-sync-stats')) {
                    WPWPS_Dashboard.loadSyncStats(widget);
                } else if (widget.hasClass('wpwps-queue-stats')) {
                    WPWPS_Dashboard.loadQueueStats(widget);
                }
            });
        },
        
        setupProcessQueue: function() {
            $('.wpwps-process-queue').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text(wpwpsDashboard.i18n.loading);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpwps_process_email_queue_manually',
                        _ajax_nonce: wpwpsDashboard.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update the widget with fresh data
                            const widget = $button.closest('.wpwps-dashboard-widget');
                            WPWPS_Dashboard.loadQueueStats(widget);
                        } else {
                            alert(response.data.message || wpwpsDashboard.i18n.error);
                        }
                    },
                    error: function() {
                        alert(wpwpsDashboard.i18n.error);
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Process Queues');
                    }
                });
            });
        },
        
        loadSyncStats: function(widget) {
            this.showLoading(widget);
            
            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'wpwps_get_sync_stats',
                    nonce: wpwpsDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        widget.find('.wpwps-widget-content').html(response.data.html).show();
                    } else {
                        widget.find('.wpwps-widget-content').html('<p>' + wpwpsDashboard.i18n.error + '</p>').show();
                    }
                },
                error: function() {
                    widget.find('.wpwps-widget-content').html('<p>' + wpwpsDashboard.i18n.error + '</p>').show();
                },
                complete: function() {
                    widget.find('.wpwps-widget-loading').hide();
                }
            });
        },
        
        loadQueueStats: function(widget) {
            this.showLoading(widget);
            
            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'wpwps_get_queue_stats',
                    nonce: wpwpsDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        widget.find('.wpwps-widget-content').html(response.data.html).show();
                    } else {
                        widget.find('.wpwps-widget-content').html('<p>' + wpwpsDashboard.i18n.error + '</p>').show();
                    }
                },
                error: function() {
                    widget.find('.wpwps-widget-content').html('<p>' + wpwpsDashboard.i18n.error + '</p>').show();
                },
                complete: function() {
                    widget.find('.wpwps-widget-loading').hide();
                }
            });
        },
        
        showLoading: function(widget) {
            widget.find('.wpwps-widget-content').hide();
            widget.find('.wpwps-widget-loading').show();
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        WPWPS_Dashboard.init();
    });
    
})(jQuery);
