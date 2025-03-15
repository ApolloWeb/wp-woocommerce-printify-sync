<?php

namespace WPWooCommercePrintifySync\Includes;

class Plugin
{
    private string $currentTime = '2025-03-15 18:50:47';
    private string $currentUser = 'ApolloWeb';
    private array $addons = [];

    public function __construct()
    {
        $this->defineConstants();
        $this->loadAddons();
    }

    private function defineConstants(): void
    {
        define('WPWPS_VERSION', '1.0.0');
        define('WPWPS_PRO_VERSION', '1.0.0');
        define('WPWPS_IS_PRO', false);
        define('WPWPS_MIN_PHP_VERSION', '7.4');
        define('WPWPS_MIN_WP_VERSION', '5.8');
        define('WPWPS_MIN_WC_VERSION', '5.0');
    }

    private function loadAddons(): void
    {
        $this->addons = [
            'r2_storage' => [
                'name' => 'R2 Storage',
                'description' => 'Offload media to Cloudflare R2 Storage',
                'version' => '1.0.0',
                'price' => 49.99,
                'url' => 'https://example.com/wpwps-r2-storage',
                'class' => 'R2StorageAddon'
            ],
            'image_optimizer' => [
                'name' => 'Image Optimizer',
                'description' => 'Automatically optimize imported images',
                'version' => '1.0.0',
                'price' => 29.99,
                'url' => 'https://example.com/wpwps-image-optimizer',
                'class' => 'ImageOptimizerAddon'
            ],
            'bulk_manager' => [
                'name' => 'Bulk Manager',
                'description' => 'Bulk import and sync management',
                'version' => '1.0.0',
                'price' => 39.99,
                'url' => 'https://example.com/wpwps-bulk-manager',
                'class' => 'BulkManagerAddon'
            ]
        ];
    }

    public function getAddons(): array
    {
        return $this->addons;
    }
}