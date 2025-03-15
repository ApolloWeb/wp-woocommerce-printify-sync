<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Addons;

abstract class BaseAddon
{
    protected string $slug;
    protected string $name;
    protected string $version;
    protected string $minCoreVersion;
    protected array $dependencies = [];

    public function __construct()
    {
        if ($this->meetsRequirements()) {
            $this->init();
        }
    }

    abstract protected function init(): void;

    protected function meetsRequirements(): bool
    {
        // Check core plugin version
        if (version_compare(WPWPS_VERSION, $this->minCoreVersion, '<')) {
            add_action('admin_notices', function() {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    sprintf(
                        esc_html__('%s requires WP WooCommerce Printify Sync version %s or higher.', 'wp-woocommerce-printify-sync'),
                        esc_html($this->name),
                        esc_html($this->minCoreVersion)
                    )
                );
            });
            return false;
        }

        return true;
    }

    public function isLicensed(): bool
    {
        // Will be implemented in each add-on
        return false;
    }
}