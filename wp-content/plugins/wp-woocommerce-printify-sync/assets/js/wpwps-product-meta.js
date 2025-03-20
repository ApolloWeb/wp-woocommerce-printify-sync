/**
 * JavaScript for Printify product meta details
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

jQuery(document).ready(function($) {
    // Only run on product edit page
    if (!$('body').hasClass('post-type-product')) {
        return;
    }
    
    const productId = $('#post_ID').val();
    
    if (!productId) {
        return;
    }
    
    // Add Printify details box after product data metabox
    $('#woocommerce-product-data').after(
        '<div class="postbox" id="printify-sync-info">' +
        '<h2 class="hndle"><span><i class="dashicons dashicons-share-alt"></i> Printify Sync Details</span></h2>' +
        '<div class="inside" id="printify-sync-details">Loading...</div>' +
        '</div>'
    );
    
    // Fetch Printify sync details
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'wpwps_get_product_sync_details',
            nonce: wpwps.nonce,
            product_id: productId
        },
        success: function(response) {
            if (response.success) {
                renderSyncDetails(response.data.details);
            } else {
                $('#printify-sync-details').html('<p class="notice notice-warning">' + response.data.message + '</p>');
            }
        },
        error: function() {
            $('#printify-sync-details').html('<p class="notice notice-error">Error loading Printify details</p>');
        }
    });
    
    /**
     * Render sync details
     * 
     * @param {Object} details The sync details
     */
    function renderSyncDetails(details) {
        const lastSyncDate = new Date(details.last_synced);
        const formattedDate = lastSyncDate.toLocaleString();
        
        let html = '<table class="widefat">' +
            '<tr><th>Printify Product ID</th><td>' + details.printify_product_id + '</td></tr>' +
            '<tr><th>Printify Provider ID</th><td>' + details.printify_provider_id + '</td></tr>' +
            '<tr><th>Last Synced</th><td>' + formattedDate + '</td></tr>' +
            '</table>';
        
        if (details.variations.length > 0) {
            html += '<h4>Variations (' + details.variations.length + ')</h4>' +
                '<table class="widefat">' +
                '<thead><tr>' +
                '<th>Variation ID</th>' +
                '<th>SKU</th>' +
                '<th>Printify Variant ID</th>' +
                '<th>Printify Cost</th>' +
                '<th>Attributes</th>' +
                '</tr></thead><tbody>';
            
            details.variations.forEach(function(variation) {
                const attributes = Object.entries(variation.attributes)
                    .map(([key, value]) => key + ': ' + value)
                    .join('<br>');
                
                html += '<tr>' +
                    '<td>' + variation.id + '</td>' +
                    '<td>' + (variation.sku || '-') + '</td>' +
                    '<td>' + variation.printify_variant_id + '</td>' +
                    '<td>$' + parseFloat(variation.printify_cost_price).toFixed(2) + '</td>' +
                    '<td>' + attributes + '</td>' +
                    '</tr>';
            });
            
            html += '</tbody></table>';
        }
        
        html += '<p><a href="#" class="button" id="refresh-printify-data">Refresh Printify Data</a></p>';
        
        $('#printify-sync-details').html(html);
    }
});
