/**
 * WooCommerce Printify Sync - Admin Dashboard Master JavaScript
 * 
 * Main JavaScript file for the admin dashboard
 */

(function($) {
    'use strict';

    // Global Dashboard Object
    var PrintifyDashboard = {
        
        // Initialize dashboard functionality
        init: function() {
            this.setupUserInfo();
            this.setupMobileMenu();
            this.setupTabNavigation();
            this.setupNotifications();
            this.setupWidgetRefresh();
            this.setupChartRefresh();
        },

        // Set up user information display
        setupUserInfo: function() {
            // Update the current date and time in the header
            function updateDateTime() {
                var now = new Date();
                var formattedDate = now.getUTCFullYear() + '-' + 
                    ('0' + (now.getUTCMonth() + 1)).slice(-2) + '-' + 
                    ('0' + now.getUTCDate()).slice(-2) + ' ' + 
                    ('0' + now.getUTCHours()).slice(-2) + ':' + 
                    ('0' + now.getUTCMinutes()).slice(-2) + ':' + 
                    ('0' + now.getUTCSeconds()).slice(-2);
                
                $('.printify-datetime').text(formattedDate);
            }
            
            // Initial update
            updateDateTime();
            
            // Update every minute
            setInterval(updateDateTime, 60000);
        },

        // Set up mobile menu toggle
        setupMobileMenu: function() {
            $('.printify-mobile-menu-toggle').on('click', function() {
                $('.printify-tabs').toggleClass('mobile-visible');
                
                // Toggle hamburger icon
                var $icon = $(this).find('i');
                if ($icon.hasClass('fa-bars')) {
                    $icon.removeClass('fa-bars').addClass('fa-times');
                } else {
                    $icon.removeClass('fa-times').addClass('fa-bars');
                }
            });
            
            // Close menu when clicking outside
            $(document).on('click', function(event) {
                if (!$(event.target).closest('.printify-mobile-menu-toggle, .printify-tabs').length) {
                    $('.printify-tabs').removeClass('mobile-visible');
                    $('.printify-mobile-menu-toggle i').removeClass('fa-times').addClass('fa-bars');
                }
            });
        },

        // Set up tab navigation
        setupTabNavigation: function() {
            $('.printify-tabs .tab-item').on('click', function() {
                var targetTab = $(this).data('tab');
                
                // Update active tab
                $('.printify-tabs .tab-item').removeClass('active');
                $(this).addClass('active');
                
                // Show related content
                $('.tab-content').hide();
                $('#' + targetTab).show();
                
                // On mobile, close the menu after selection
                if (window.innerWidth < 768) {
                    $('.printify-tabs').removeClass('mobile-visible');
                    $('.printify-mobile-menu-toggle i').removeClass('fa-times').addClass('fa-bars');
                }
            });
        },

        // Set up notification system
        setupNotifications: function() {
            // Create toast container if it doesn't exist
            if ($('.printify-toast-container').length === 0) {
                $('body').append('<div class="printify-toast-container"></div>');
            }
            
            // Function to show a toast notification
            window.showToast = function(title, message, type) {
                var icons = {
                    'success': 'fa-check-circle',
                    'danger': 'fa-exclamation-circle',
                    'warning': 'fa-exclamation-triangle',
                    'info': 'fa-info-circle'
                };
                
                var iconClass = icons[type] || icons.info;
                var toast = $(`
                    <div class="printify-toast">
                        <div class="printify-toast-header">
                            <span class="icon"><i class="fas ${iconClass}"></i></span>
                            <span class="title">${title}</span>
                            <button type="button" class="close">&times;</button>
                        </div>
                        <div class="printify-toast-body">
                            ${message}
                        </div>
                    </div>
                `);
                
                // Append toast to container
                $('.printify-toast-container').append(toast);
                
                // Animate and show
                setTimeout(function() {
                    toast.addClass('show');
                }, 100);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    dismissToast(toast);
                }, 5000);
                
                // Close button handler
                toast.find('.close').on('click', function() {
                    dismissToast(toast);
                });
                
                // Function to dismiss toast
                function dismissToast(toast) {
                    toast.removeClass('show');
                    setTimeout(function() {
                        toast.remove();
                    }, 300);
                }
            };
            
            // Function to show alert in a specific container
            window.showAlert = function(container, message, type, dismissible = true) {
                var alert = $(`
                    <div class="printify-alert printify-alert-${type} ${dismissible ? 'printify-alert-dismissible' : ''}">
                        ${message}
                        ${dismissible ? '<button type="button" class="close">&times;</button>' : ''}
                    </div>
                `);
                
                // Append alert to container
                $(container).prepend(alert);
                
                if (dismissible) {
                    // Close button handler
                    alert.find('.close').on('click', function() {
                        alert.remove();
                    });
                }
                
                return alert;
            };
        },

        // Set up widget refresh functionality
        setupWidgetRefresh: function() {
            $('.widget-refresh').on('click', function(e) {
                e.preventDefault();
                
                var $widget = $(this).closest('.dashboard-widget');
                var widgetId = $widget.data('widget-id');
                
                // Add loading spinner
                $widget.find('.widget-content').addClass('loading');
                $widget.find('.widget-content').append('<div class="widget-loader"><i class="fas fa-spinner fa-spin"></i></div>');
                
                // AJAX call to refresh widget data
                $.ajax({
                    url: printifySyncAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'refresh_widget',
                        widget_id: widgetId,
                        nonce: printifySyncAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $widget.find('.widget-content').html(response.data.content);
                            showToast('Widget Updated', 'Widget data has been refreshed', 'success');
                        } else {
                            showToast('Update Failed', response.data.message, 'danger');
                        }
                    },
                    error: function() {
                        showToast('Error', 'Could not connect to server', 'danger');
                    },
                    complete: function() {
                        $widget.find('.widget-loader').remove();
                        $widget.find('.widget-content').removeClass('loading');
                    }
                });
            });
        },

        // Set up chart refresh and functionality
        setupChartRefresh: function() {
            // Chart period filters
            $('.sales-filter .filter-btn').on('click', function() {
                var $this = $(this);
                var period = $this.data('period');
                var chartId = $this.closest('.chart-container').data('chart-id');
                
                // Update active state
                $this.siblings().removeClass('active');
                $this.addClass('active');
                
                // Show loading state
                $this.closest('.chart-container').addClass('loading');
                
                // AJAX call to get chart data
                $.ajax({
                    url: printifySyncAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_chart_data',
                        chart_id: chartId,
                        period: period,
                        nonce: printifySyncAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update chart data
                            if (window.printifyCharts && window.printifyCharts[chartId]) {
                                window.printifyCharts[chartId].data.labels = response.data.labels;
                                window.printifyCharts[chartId].data.datasets = response.data.datasets;
                                window.printifyCharts[chartId].update();
                            }
                        } else {
                            showToast('Error', 'Could not load chart data', 'danger');
                        }
                    },
                    error: function() {
                        showToast('Error', 'Could not connect to server', 'danger');
                    },
                    complete: function() {
                        $this.closest('.chart-container').removeClass('loading');
                    }
                });
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PrintifyDashboard.init();
    });

})(jQuery);