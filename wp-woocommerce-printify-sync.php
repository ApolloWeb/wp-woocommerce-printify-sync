    /**
     * Get geolocator
     *
     * @return \ApolloWeb\WPWooCommercePrintifySync\Geolocation\Geolocator
     */
    public function get_geolocator() {
        return \ApolloWeb\WPWooCommercePrintifySync\Geolocation\Geolocator::get_instance();
    }
    
    /**
     * Get background processor
     *
     * @return \ApolloWeb\WPWooCommercePrintifySync\Processing\BackgroundProcessor
     */
    public function get_background_processor() {
        return \ApolloWeb\WPWooCommercePrintifySync\Processing\BackgroundProcessor::get_instance();
    }
    
    /**
     * Get installer
     *
     * @return \ApolloWeb\WPWooCommercePrintifySync\Install\Installer
     */
    public function get_installer() {
        return \ApolloWeb\WPWooCommercePrintifySync\Install\Installer::get_instance();
    }
}

// Initialize plugin
function wpwprintifysync_init() {
    return WP_WooCommerce_Printify_Sync::get_instance();
}

// Start the plugin
wpwprintifysync_init();