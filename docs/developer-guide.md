/**
 * Modify product prices during import/update
 */
function my_custom_product_pricing($wc_product_data, $printify_data) {
    // Add a 10% markup to all products
    if (isset($wc_product_data['regular_price'])) {
        $wc_product_data['regular_price'] *= 1.10;
    }
    
    return $wc_product_data;
}
add_filter('wpwprintifysync_wc_product_data', 'my_custom_product_pricing', 10, 2);