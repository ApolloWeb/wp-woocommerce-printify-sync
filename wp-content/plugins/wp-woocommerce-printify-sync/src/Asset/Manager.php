<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Asset;

use ApolloWeb\WPWooCommercePrintifySync\Asset\Collection\{ScriptCollection, StyleCollection};

class Manager
{
    private ScriptCollection $scripts;
    private StyleCollection $styles;
    private array $pageAssets = [
        'dashboard' => [
            'styles' => ['dashboard', 'charts'],
            'scripts' => ['dashboard', 'charts'],
        ],
        'products' => [
            'styles' => ['products', 'tables'],
            'scripts' => ['products', 'datatables'],
        ],
        'settings' => [
            'styles' => ['settings'],
            'scripts' => ['settings'],
        ],
    ];

    public function __construct(
        ScriptCollection $scripts,
        StyleCollection $styles
    ) {
        $this->scripts = $scripts;
        $this->styles = $styles;
        
        $this->registerAssets();
    }

    private function registerAssets(): void
    {
        // Register core assets
        $this->registerCoreAssets();
        
        // Register vendor assets
        $this->registerVendorAssets();
        
        // Register page assets
        $this->registerPageAssets();
        
        // Register the collections
        $this->scripts->register();
        $this->styles->register();
    }

    private function registerCoreAssets(): void
    {
        // Core CSS
        $this->styles->add(new Style(
            'wpwps-core',
            $this->getAssetUrl('css/core.css'),
            [],
            WPWPS_VERSION
        ));

        // Core JS
        $this->scripts->add(new Script(
            'wpwps-core',
            $this->getAssetUrl('js/core.js'),
            ['jquery'],
            WPWPS_VERSION,
            true,
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpwps-admin')
            ]
        ));
    }

    private function registerPageAssets(): void
    {
        foreach ($this->pageAssets as $page => $assets) {
            foreach ($assets['styles'] ?? [] as $style) {
                $this->styles->add(new Style(
                    "wpwps-{$style}",
                    $this->getAssetUrl("css/{$style}.css"),
                    ['wpwps-core'],
                    WPWPS_VERSION
                ));
            }

            foreach ($assets['scripts'] ?? [] as $script) {
                $this->scripts->add(new Script(
                    "wpwps-{$script}",
                    $this->getAssetUrl("js/{$script}.js"),
                    ['wpwps-core'],
                    WPWPS_VERSION,
                    true
                ));
            }
        }
    }

    private function registerVendorAssets(): void
    {
        // Chart.js
        $this->scripts->add(new Script(
            'chart.js',
            $this->getAssetUrl('vendor/chart.js/chart.min.js'),
            [],
            '3.7.0'
        ));

        // DataTables
        $this->styles->add(new Style(
            'datatables',
            $this->getAssetUrl('vendor/datatables/datatables.min.css'),
            [],
            '1.10.24'
        ));

        $this->scripts->add(new Script(
            'datatables',
            $this->getAssetUrl('vendor/datatables/datatables.min.js'),
            ['jquery'],
            '1.10.24'
        ));
    }

    public function enqueuePageAssets(string $page): void
    {
        if (!isset($this->pageAssets[$page])) {
            return;
        }

        foreach ($this->pageAssets[$page]['styles'] ?? [] as $style) {
            $this->styles->enqueue("wpwps-{$style}");
        }

        foreach ($this->pageAssets[$page]['scripts'] ?? [] as $script) {
            $this->scripts->enqueue("wpwps-{$script}");
        }
    }

    private function getAssetUrl(string $path): string
    {
        return plugins_url("assets/{$path}", WPWPS_PLUGIN_FILE);
    }
}