/**
 * Common Admin JavaScript
 * For all admin pages in WP WooCommerce Printify Sync
 */

jQuery(document).ready(function($) {
    // Mobile menu toggle
    $('#mobile-menu-toggle').on('click', function() {
        $('#printify-navigation').toggleClass('mobile-visible');
        $(this).toggleClass('active');
    });
    
    // Sync Now button functionality
    $('#sync-now-btn').on('click', function() {
        if ($(this).hasClass('disabled')) {
            return;
        }
        
        // Disable the button and show spinner
        $(this).addClass('disabled');
        $(this).html('<i class="fas fa-sync fa-spin"></i> Syncing...');
        
        // Make AJAX call to initiate sync
        $.ajax({
            url: printifySyncAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'printify_sync_now',
                nonce: printifySyncAdmin.nonce
            },
            success: function(response) {
                $('#sync-now-btn').removeClass('disabled');
                
                if (response.success) {
                    $('#sync-now-btn').html('<i class="fas fa-check"></i> Synced');
                    // Reset button text after 3 seconds
                    setTimeout(function() {
                        $('#sync-now-btn').html('<i class="fas fa-sync"></i> Sync Now');
                    }, 3000);
                } else {
                    $('#sync-now-btn').html('<i class="fas fa-exclamation-triangle"></i> Failed');
                   