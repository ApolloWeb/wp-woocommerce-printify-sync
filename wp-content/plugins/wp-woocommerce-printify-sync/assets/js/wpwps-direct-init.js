/**
 * Direct initialization script for cases where wp_localize_script might not be working
 */
(function() {
    // Check if wpwps_data is already defined
    if (typeof window.wpwps_data === 'undefined') {
        console.log('wpwps_data not found, initializing directly');
        
        // Create fallback data object
        window.wpwps_data = {
            ajax_url: '/wp-admin/admin-ajax.php',
            nonce: '',  // We'll need to update this dynamically
            debug_mode: true
        };
        
        // Try to fetch the nonce from the page
        var nonceField = document.querySelector('input[name="_wpnonce"]');
        if (nonceField) {
            window.wpwps_data.nonce = nonceField.value;
        }
        
        console.log('wpwps_data initialized:', window.wpwps_data);
    }
})();
