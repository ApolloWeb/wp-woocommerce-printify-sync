/**
 * Shipping page JavaScript.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

(function($) {
    'use strict';

    /**
     * Show alert message.
     *
     * @param {string} message Alert message.
     * @param {string} type    Alert type.
     */
    function showAlert(message, type = 'success') {
        const alert = $('<div class="wpwps-alert wpwps-alert-' + type + '">' + message + '</div>');
        $('.wpwps-alert-container').html(alert);

        // Scroll to alert
        $('html, body').animate({
            scrollTop: $('.wpwps-alert-container').offset().top - 50
        }, 500);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Sync shipping profiles.
     */
    function syncShippingProfiles() {
        $('#sync-profiles').prop('disabled', true).addClass('loading');
        
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_sync_shipping_profiles',
                nonce: wpwps.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAlert(response.data.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert(response.data.message, 'danger');
                    $('#sync-profiles').prop('disabled', false).removeClass('loading');
                }
            },
            error: function() {
                showAlert('An error occurred while syncing shipping profiles. Please try again.', 'danger');
                $('#sync-profiles').prop('disabled', false).removeClass('loading');
            }
        });
    }

    /**
     * Sync shipping zones.
     */
    function syncShippingZones() {
        $('#sync-zones').prop('disabled', true).addClass('loading');
        
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_sync_shipping_zones',
                nonce: wpwps.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAlert(response.data.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert(response.data.message, 'danger');
                    $('#sync-zones').prop('disabled', false).removeClass('loading');
                }
            },
            error: function() {
                showAlert('An error occurred while syncing shipping zones. Please try again.', 'danger');
                $('#sync-zones').prop('disabled', false).removeClass('loading');
            }
        });
    }

    /**
     * Initialize functions on document ready.
     */
    $(document).ready(function() {
        // Sync shipping profiles
        $('#sync-profiles').on('click', function(e) {
            e.preventDefault();
            syncShippingProfiles();
        });
        
        // Sync shipping zones
        $('#sync-zones').on('click', function(e) {
            e.preventDefault();
            syncShippingZones();
        });
        
        // Show shipping methods for a provider
        $('.show-shipping-methods').on('click', function(e) {
            e.preventDefault();
            const providerId = $(this).data('provider-id');
            $('.provider-methods-' + providerId).slideToggle();
            $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
        });
    });

})(jQuery);
