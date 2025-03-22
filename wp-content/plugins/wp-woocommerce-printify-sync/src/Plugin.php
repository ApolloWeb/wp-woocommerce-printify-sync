<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Plugin {
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function init(): void {
        $this->initHooks();
        $this->loadTextdomain();
        
        if (is_admin()) {
            $this->container->get('admin')->init();
        }
    }

    private function initHooks(): void {
        register_activation_hook(WPPS_FILE, [$this, 'activate']);
        register_deactivation_hook(WPPS_FILE, [$this, 'deactivate']);
    }

    private function loadTextdomain(): void {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(plugin_basename(WPPS_FILE)) . '/languages'
        );
    }

    public function activate(): void {
        // Activation logic
    }

    public function deactivate(): void {
        // Deactivation logic
    }
}
