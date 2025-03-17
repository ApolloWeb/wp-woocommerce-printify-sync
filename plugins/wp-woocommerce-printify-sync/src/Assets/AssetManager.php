<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Assets;

class AssetManager
{
    private const ADMIN_PAGES = [
        'dashboard' => [
            'styles' => [
                'dashboard' => [
                    'path' => 'admin/css/dashboard.css',
                    'deps' => ['wpwps-core'],
                    'version' => WPWPS_VERSION
                ],
                'charts' => [
                    'path' => 'admin/css/charts.css',
                    'deps' => ['chart.js'],
                    'version' => WPWPS_VERSION
                ]
            ],
            'scripts' => [
                'dashboard' => [
                    'path' => 'admin/js/dashboard.js',
                    'deps' => ['jquery', 'wpwps-core', 'chart.js'],
                    'version' => WPWPS_VERSION,
                    'in_footer' => true,
                    'localize' => [
                        'name' => 'wpwpsDashboard',
                        'data' => ['ajaxUrl', 'nonce']
                    ]
                ]
            ]
        ],
        'products' => [
            'styles' => [
                'products' => [
                    'path' => 'admin/css/products.css',
                    'deps' => ['wpwps-core', 'datatables'],
                    'version' => WPWPS_VERSION
                ]
            ],
            'scripts' => [
                'products' => [
                    'path' => 'admin/js/products.js',
                    'deps' => ['jquery', 'wpwps-core', 'datatables'],
                    'version' => WPWPS_VERSION,
                    'in_footer' => true,
                    'localize' => [
                        'name' => 'wpwpsProducts',
                        'data' => ['ajaxUrl', 'nonce']
                    ]
                ]
            ]
        ]
    ];

    private array $localizeData = [];

    public function __construct()
    {
        $this->localizeData = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-admin'),
            'i18n' => [
                'confirm' => __('Are you sure?', 'wp-woocommerce-printify-sync'),
                'success' => __('Success!', 'wp-woocommerce-printify-sync'),
                'error' => __('Error occurred', 'wp-woocommerce-printify-sync')
            ]
        ];
    }

    public function register(): void
    {
        // Register core assets
        add_action('admin_enqueue_scripts', [$this, 'registerCoreAssets']);
        
        // Register page-specific assets
        add_action('admin_enqueue_scripts', [$this, 'registerPageAssets']);
    }

    public function registerCoreAssets(): void
    {
        // Core CSS
        wp_register_style(
            'wpwps-core',
            $this->getAssetUrl('admin/css/core.css'),
            [],
            WPWPS_VERSION
        );

        // Core JS
        wp_register_script(
            'wpwps-core',
            $this->getAssetUrl('admin/js/core.js'),
            ['jquery'],
            WPWPS_VERSION,
            true
        );

        // Core vendor dependencies
        $this->registerVendorAssets();

        // Localize core script
        wp_localize_script(
            'wpwps-core',
            'wpwpsCore',
            $this->localizeData
        );
    }

    public function registerPageAssets(string $hook): void
    {
        if (!$this->isPluginPage($hook)) {
            return;
        }

        $page = $this->getPageFromHook($hook);
        
        if (!isset(self::ADMIN_PAGES[$page])) {
            return;
        }

        $this->enqueuePageAssets(self::ADMIN_PAGES[$page]);
    }

    private function registerVendorAssets(): void
    {
        // Chart.js
        wp_register_script(
            'chart.js',
            $this->getAssetUrl('vendor/chart.js/chart.min.js'),
            [],
            '3.7.0',
            true
        );

        // DataTables
        wp_register_style(
            'datatables',
            $this->getAssetUrl('vendor/datatables/datatables.min.css'),
            [],
            '1.10.24'
        );

        wp_register_script(
            'datatables',
            $this->getAssetUrl('vendor/datatables/datatables.min.js'),
            ['jquery'],
            '1.10.24',
            true
        );
    }

    private function enqueuePageAssets(array $config): void
    {
        // Enqueue styles
        if (isset($config['styles'])) {
            foreach ($config['styles'] as $handle => $style) {
                wp_enqueue_style(
                    "wpwps-{$handle}",
                    $this->getAssetUrl($style['path']),
                    $style['deps'] ?? [],
                    $style['version'] ?? WPWPS_VERSION
                );
            }
        }

        // Enqueue scripts
        if (isset($config['scripts'])) {
            foreach ($config['scripts'] as $handle => $script) {
                wp_enqueue_script(
                    "wpwps-{$handle}",
                    $this->getAssetUrl($script['path']),
                    $script['deps'] ?? [],
                    $script['version'] ?? WPWPS_VERSION,
                    $script['in_footer'] ?? true
                );

                // Localize if needed
                if (isset($script['localize'])) {
                    $data = array_intersect_key(
                        $this->localizeData,
                        array_flip($script['localize']['data'])
                    );

                    wp_localize_script(
                        "wpwps-{$handle}",
                        $script['localize']['name'],
                        $data
                    );
                }
            }
        }
    }

    private function getAssetUrl(string $path): string
    {
        return plugins_url($path, WPWPS_PLUGIN_FILE);
    }

    private function isPluginPage(string $hook): bool
    {
        return strpos($hook, 'printify-') !== false;
    }

    private function getPageFromHook(string $hook): string
    {
        return str_replace('printify-', '', $hook);
    }
}