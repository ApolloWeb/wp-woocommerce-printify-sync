/**
 * Orders JavaScript.
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
     * Load orders via AJAX.
     *
     * @param {string} search Search term.
     * @param {string} status Order status.
     */
    function loadOrders(search = '', status = '') {
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_get_orders',
                nonce: wpwps.nonce,
                search: search,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    // Update orders table
                    const orders = response.data.orders;
                    const tbody = $('.recent-orders tbody');
                    tbody.empty();

                    if (orders.length === 0) {
                        tbody.append('<tr><td colspan="6" class="text-muted"><?php esc_html_e('No orders found.', 'wp-woocommerce-printify-sync'); ?></td></tr>');
                    } else {
                        orders.forEach(order => {
                            const row = `
                                <tr>
                                    <td><a href="${order.edit_link}">${order.order_id}</a></td>
                                    <td>${order.date}</td>
                                    <td>${order.status}</td>
                                    <td>${order.total}</td>
                                    <td>${order.items}</td>
                                    <td>${order.printify_ids}</td>
                                </tr>
                            `;
                            tbody.append(row);
                        });
                    }
                } else {
                    showAlert(response.data.message, 'danger');
                }
            },
            error: function() {
                showAlert('<?php esc_html_e('An error occurred. Please try again.', 'wp-woocommerce-printify-sync'); ?>', 'danger');
            }
        });
    }

    /**
     * Initialize functions on document ready.
     */
    $(document).ready(function() {
        // Search button click
        $('#search-button').on('click', function() {
            const search = $('#order-search').val();
            const status = $('#order-status-filter').val();
            loadOrders(search, status);
        });

        // Filter button click
        $('#filter-button').on('click', function() {
            const search = $('#order-search').val();
            const status = $('#order-status-filter').val();
            loadOrders(search, status);
        });
    });

})(jQuery);
