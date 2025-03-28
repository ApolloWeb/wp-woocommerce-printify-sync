<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Plugin {
    private array $providers = [];

    public function boot(): void {
        // Initialize third-party libraries
        LibraryLoader::initLibraries();
        
        $this->registerProviders();
        $this->bootProviders();
        $this->initHooks();
    }

    private function registerProviders(): void {
        $this->providers = [
            \ApolloWeb\WPWooCommercePrintifySync\Providers\DashboardProvider::class,
            \ApolloWeb\WPWooCommercePrintifySync\Providers\SettingsProvider::class,
        ];
    }

    private function bootProviders(): void {
        foreach ($this->providers as $providerClass) {
            $provider = new $providerClass();
            if ($provider instanceof ServiceProvider) {
                $provider->register();
            }
        }
    }

    private function initHooks(): void {
        add_action('init', [$this, 'loadTextDomain']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function loadTextDomain(): void {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(plugin_basename(WPWPS_FILE)) . '/languages/'
        );
    }

    public function enqueueAdminAssets(): void {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wpwps') === false) {
            return;
        }

        wp_enqueue_style('wpwps-bootstrap', WPWPS_URL . 'assets/core/css/bootstrap.min.css', [], WPWPS_VERSION);
        wp_enqueue_style('wpwps-fontawesome', WPWPS_URL . 'assets/core/css/fontawesome.min.css', [], WPWPS_VERSION);
        wp_enqueue_script('wpwps-bootstrap', WPWPS_URL . 'assets/core/js/bootstrap.bundle.min.js', ['jquery'], WPWPS_VERSION, true);
        wp_enqueue_script('wpwps-fontawesome', WPWPS_URL . 'assets/core/js/fontawesome.min.js', [], WPWPS_VERSION, true);
        wp_enqueue_script('wpwps-chartjs', WPWPS_URL . 'assets/core/js/chart.min.js', [], WPWPS_VERSION, true);
    }
}