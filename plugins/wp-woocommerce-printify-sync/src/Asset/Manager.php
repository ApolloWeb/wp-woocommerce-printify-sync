<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Asset;

use ApolloWeb\WPWooCommercePrintifySync\Asset\Collection\{ScriptCollection, StyleCollection};

class Manager
{
    private const VERSION = '2025-03-17-19-33-45'; // Current timestamp
    private ScriptCollection $scripts;
    private StyleCollection $styles;
    
    private array $pageAssets = [
        'dashboard' => [
            'styles' => ['dashboard', 'charts'],
            'scripts' => ['dashboard', 'charts'],
            'deps' => ['wpwps-chartjs', 'wpwps-fontawesome']
        ],
        'products' => [
            'styles' => ['products', 'tables'],
            'scripts' => ['products', 'datatables'],
            'deps' => ['wpwps-fontawesome', 'datatables']
        ],
        'settings' => [
            'styles' => ['settings'],
            'scripts' => ['settings'],
            'deps' => ['wpwps-fontawesome']
        ]
    ];

    public function __construct(
        ScriptCollection $scripts,
        StyleCollection $styles
    ) {
        $this->scripts = $scripts;
        $this->styles = $styles;
        
        $this->registerAssets();
    }

    private function registerAssets(): void {
        // Register vendor assets first
        $this->registerVendorAssets();
        
        // Then register core assets
        $this->registerCoreAssets();
        
        // Finally register page assets
        $this->registerPageAssets();
        
        // Register the collections
        $this->scripts->register();
        $this->styles->register();
    }

    private function registerVendorAssets(): void {
        // Chart.js - Local version
        $this->scripts->add(new Script(
            'wpwps-chartjs',
            $this->getAssetUrl('vendor/chart.js/chart.min.js'),
            [],
            '4.4.0'
        ));

        // FontAwesome - Local version (all components)
        $this->styles->add(new Style(
            'wpwps-fontawesome',
            $this->getAssetUrl('vendor/fontawesome/css/all.min.css'),
            [],
            '6.5.1'
        ));
        
        // FontAwesome individual components if needed
        $this->scripts->add(new Script(
            'wpwps-fontawesome-solid',
            $this->getAssetUrl('vendor/fontawesome/js/solid.min.js'),
            [],
            '6.5.1'
        ));
        
        $this->scripts->add(new Script(
            'wpwps-fontawesome-brands',
            $this->getAssetUrl('vendor/fontawesome/js/brands.min.js'),
            [],
            '6.5.1'
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

    private function registerCoreAssets(): void {
        // Core CSS
        $this->styles->add(new Style(
            'wpwps-core',
            $this->getAssetUrl('css/core.css'),
            ['wpwps-fontawesome'],
            self::VERSION
        ));

        // Core JS
        $this->scripts->add(new Script(
            'wpwps-core',
            $this->getAssetUrl('js/core.js'),
            ['jquery', 'wpwps-chartjs', 'wpwps-fontawesome-solid', 'wpwps-fontawesome-brands'],
            self::VERSION,
            true,
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpwps-admin')
            ]
        ));
    }

    private function registerPageAssets(): void {
        foreach ($this->pageAssets as $page => $assets) {
            foreach ($assets['styles'] ?? [] as $style) {
                $this->styles->add(new Style(
                    "wpwps-{$style}",
                    $this->getAssetUrl("css/{$style}.css"),
                    array_merge(['wpwps-core'], $assets['deps'] ?? []),
                    self::VERSION
                ));
            }

            foreach ($assets['scripts'] ?? [] as $script) {
                $this->scripts->add(new Script(
                    "wpwps-{$script}",
                    $this->getAssetUrl("js/{$script}.js"),
                    array_merge(['wpwps-core'], $assets['deps'] ?? []),
                    self::VERSION,
                    true
                ));
            }
        }
    }

    private function getAssetUrl(string $path): string {
        return plugins_url("assets/{$path}", WPWPS_PLUGIN_FILE);
    }
}