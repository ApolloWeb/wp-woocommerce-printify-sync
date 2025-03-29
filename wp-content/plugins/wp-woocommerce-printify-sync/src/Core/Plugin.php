<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Plugin
{
    private array $providers = [
        \ApolloWeb\WPWooCommercePrintifySync\Providers\DashboardProvider::class,
        \ApolloWeb\WPWooCommercePrintifySync\Providers\SettingsProvider::class,
    ];

    public function boot(): void
    {
        $this->checkDependencies();
        $this->initializeProviders();
        $this->loadTextDomain();
        $this->registerHooks();
    }

    private function checkDependencies(): void
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>';
                echo esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync');
                echo '</p></div>';
            });
            return;
        }
    }

    private function initializeProviders(): void
    {
        foreach ($this->providers as $provider) {
            if (class_exists($provider)) {
                $instance = new $provider();
                if (method_exists($instance, 'register')) {
                    $instance->register();
                }
            }
        }
    }

    private function loadTextDomain(): void
    {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(WPWPS_BASENAME) . '/languages/'
        );
    }

    private function registerHooks(): void
    {
        register_activation_hook(WPWPS_FILE, [$this, 'activate']);
        register_deactivation_hook(WPWPS_FILE, [$this, 'deactivate']);
    }

    public function activate(): void
    {
        // Create necessary database tables
        // Set default options
        flush_rewrite_rules();
    }

    public function deactivate(): void
    {
        flush_rewrite_rules();
    }
}