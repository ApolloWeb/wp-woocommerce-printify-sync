/**
 * Products JavaScript.
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
     * Load products via AJAX.
     *
     * @param {string} search Search term.
     * @param {string} status Product status.
     */
    function loadProducts(search = '', status = '') {
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_get_products',
                nonce: wpwps.nonce,
                search: search,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    // Update products table
                    const products = response.data.products;
                    const tbody = $('.recent-products tbody');
                    tbody.empty();

                    if (products.length === 0) {
                        tbody.append('<tr><td colspan="4" class="text-muted"><?php esc_html_e('No products found.', 'wp-woocommerce-printify-sync'); ?></td></tr>');
                    } else {
                        products.forEach(product => {
                            const row = `
                                <tr>
                                    <td><a href="${product.edit_link}">${product.product_id}</a></td>
                                    <td>${product.title}</td>
                                    <td>${product.status}</td>
                                    <td>${product.printify_id}</td>
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
            const search = $('#product-search').val();
            const status = $('#product-status-filter').val();
            loadProducts(search, status);
        });

        // Filter button click
        $('#filter-button').on('click', function() {
            const search = $('#product-search').val();
            const status = $('#product-status-filter').val();
            loadProducts(search, status);
        });
    });

})(jQuery);
