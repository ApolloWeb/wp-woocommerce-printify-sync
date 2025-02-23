<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Handle plugin dependencies using TGM Plugin Activation
 */
class PluginDependencies {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        require_once dirname( __FILE__ ) . '/class-tgm-plugin-activation.php';
        add_action( 'tgmpa_register', array( $this, 'register_required_plugins' ) );
    }

    /**
     * Register the required plugins for this plugin.
     */
    public function register_required_plugins() {
        $plugins = array(
            array(
                'name'      => 'WooCommerce',
                'slug'      => 'woocommerce',
                'required'  => true,
                'version'   => '7.0.0' // Minimum version required
            )
        );
        
        $config = array(
            'id'           => 'wp-woocommerce-printify-sync',    // Unique ID for TGMPA
            'default_path' => '',                                // Default absolute path
            'menu'         => 'tgmpa-install-plugins',           // Menu slug
            'parent_slug'  => 'plugins.php',                     // Parent menu slug
            'capability'   => 'manage_options',                  // Capability needed to view plugin install page
            'has_notices'  => true,                             // Show admin notices
            'dismissable'  => true,                             // Allow users to dismiss notices
            'dismiss_msg'  => '',                               // Message to output right before the plugins table
            'is_automatic' => false,                            // Automatically activate plugins after installation
            'message'      => '',                               // Message to output right before the plugins table
            'strings'      => array(
                'notice_can_install_required' => _n_noop(
                    'WP WooCommerce Printify Sync requires the following plugin: %1$s.',
                    'WP WooCommerce Printify Sync requires the following plugins: %1$s.',
                    'wp-woocommerce-printify-sync'
                ),
            )
        );

        tgmpa( $plugins, $config );
    }
}