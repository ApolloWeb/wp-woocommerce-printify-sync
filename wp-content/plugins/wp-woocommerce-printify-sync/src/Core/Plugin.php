<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Plugin
{
    protected array $providers = [];

    public function boot(): void
    {
        $this->registerProviders([
            // Register providers here
        ]);

        register_activation_hook($this->getPluginFile(), [$this, 'onActivate']);
        register_deactivation_hook($this->getPluginFile(), [$this, 'onDeactivate']);
    }

    protected function registerProviders(array $providers): void
    {
        foreach ($providers as $providerClass) {
            if (class_exists($providerClass)) {
                $provider = new $providerClass;
                if ($provider instanceof ServiceProvider) {
                    $provider->register();
                    $this->providers[] = $provider;
                }
            }
        }
    }

    public function onActivate(): void
    {
        foreach ($this->providers as $provider) {
            $provider->bootActivation();
        }
    }

    public function onDeactivate(): void
    {
        foreach ($this->providers as $provider) {
            $provider->bootDeactivation();
        }
    }

    protected function getPluginFile(): string
    {
        return dirname(__DIR__, 2) . '/wp-woocommerce-printify-sync.php';
    }
}
