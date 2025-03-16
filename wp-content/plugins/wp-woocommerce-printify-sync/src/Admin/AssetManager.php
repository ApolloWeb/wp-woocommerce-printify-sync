<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AssetManager
{
    private const PAGES = [
        'dashboard' => [
            'css' => ['dashboard', 'charts'],
            'js' => ['dashboard', 'charts'],
            'deps' => ['chart.js', 'bootstrap']
        ],
        'products' => [
            'css' => ['products', 'tables'],
            'js' => ['products', 'datatables'],
            'deps' => ['datatables']
        ],
        'orders' => [
            'css' => ['orders', 'tables'],
            'js' => ['orders', 'datatables'],
            'deps' => ['datatables']
        ],
        'settings' => [
            'css' => ['settings'],
            'js' => ['settings'],
            'deps' => []
        ]
    ];

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        if (strpos($hook, 'printify-') === false) {
            return;
        }

        // Core assets
        wp_enqueue_style(
            'printify-core',
            plugins_url('assets/css/core.css', WPWPS_PLUGIN_FILE),
            ['bootstrap'],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'printify-core',
            plugins_url('assets/js/core.js', WPWPS_PLUGIN_FILE),
            ['jquery', 'bootstrap'],
            WPWPS_VERSION,
            true
        );

        // Page specific assets
        $page = str_replace('printify-', '', $hook);
        if (isset(self::PAGES[$page])) {
            $this->enqueuePageAssets($page);
        }
    }

    private function enqueuePageAssets(string $page): void
    {
        $config = self::PAGES[$page];

        foreach ($config['css'] as $style) {
            wp_enqueue_style(
                "printify-{$style}",
                plugins_url("assets/css/{$style}.css", WPWPS_PLUGIN_FILE),
                [],
                WPWPS_VERSION
            );
        }

        foreach ($config['js'] as $script) {
            wp_enqueue_script(
                "printify-{$script}",
                plugins_url("assets/js/{$script}.js", WPWPS_PLUGIN_FILE),
                $config['deps'],
                WPWPS_VERSION,
                true
            );
        }
    }
}