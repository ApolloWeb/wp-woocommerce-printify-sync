/**
 * WooCommerce Printify Sync - Dashboard JavaScript
 */

(function($) {
    'use strict';

    // Dashboard Namespace
    var PrintifySyncDashboard = {
        init: function() {
            this.setupDateTime();
            this.setupMobileMenu();
            this.setupTabNavigation();
            this.setupWidgetToggles();
            this.setupNotifications();
            this.initAlerts();
        },

        // Update the datetime display
        setupDateTime: function() {
            function updateDateTime() {
                var now = new Date();
                
                // Format date in YYYY-MM-DD HH:MM:SS
                var formattedDate = now.getUTCFullYear() + '-' + 
                    ('0' + (now.getUTCMonth() + 1)).slice(-2) + '-' + 
                    ('0' + now.getUTCDate()).slice(-2) + ' ' + 
                    ('0' + now.getUTCHours()).slice(-2) + ':' + 
                    ('0' + now.getUTCMinutes()).slice(-2) + ':' + 
                    ('0' + now.getUTCSeconds()).slice(-2);
                
                $('.printify-datetime').text(formattedDate);
            }
            
            // Update immediately and then every minute
            updateDateTime