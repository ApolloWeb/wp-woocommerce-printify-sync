<?php
// ...existing code...

// Register scripts
wp_register_script(
    'wpwps-products',
    plugins_url('assets/js/wpwps-products.js', $this->pluginFile),
    ['jquery'],
    $this->version,
    true
);

wp_register_script(
    'wpwps-import-progress',
    plugins_url('assets/js/wpwps-import-progress.js', $this->pluginFile),
    ['jquery'],
    $this->version,
    true
);

// ...existing code...

// For products page
if ($page === 'wpwps-products') {
    wp_localize_script('wpwps-products', 'wpwps_data', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wpwps_nonce'),
        'shop_id' => get_option('wpwps_printify_shop_id', '')
    ]);
    
    // Add same data to import progress script
    wp_localize_script('wpwps-import-progress', 'wpwps_data', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wpwps_nonce'),
        'shop_id' => get_option('wpwps_printify_shop_id', '')
    ]);
}

// ...existing code...
