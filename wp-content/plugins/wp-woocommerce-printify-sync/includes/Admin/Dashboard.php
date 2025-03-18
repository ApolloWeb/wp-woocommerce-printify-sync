<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class Dashboard {
    // ...existing code...
    
    public function add_menu_page() {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-dashboard',
            [$this, 'render_dashboard'],
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black" width="36px" height="36px"><path d="M21 4H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H3V6h18v12zM8 10h8v2H8v-2z"/></svg>'),
            58
        );
    }
    
    // ...existing code...
}
