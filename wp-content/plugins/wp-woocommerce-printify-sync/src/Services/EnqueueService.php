<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\AssetHelper;

class EnqueueService
{
    private string $currentTime;
    private string $currentUser;
    private array $registeredAssets = [];

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime; // 2025-03-15 18:05:08
        $this->currentUser = $currentUser; // ApolloWeb
        
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        if (!str_contains($hook, 'wpwps')) {
            return;
        }

        // Common assets
        $this->enqueueCommonAssets();

        // Page specific assets
        $page = $this->getCurrentPage();
        $assets = AssetHelper::getPageAssets($page);

        foreach ($assets['css'] as $style) {
            $this->enqueueStyle($style);
        }

        foreach ($assets['js'] as $script) {
            $this->enqueueScript($script);
        }
    }

    private function enqueueCommonAssets(): void
    {
        // Font Awesome
        wp_enqueue_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            [],
            '6.5.1'
        );

        // Common CSS
        wp_enqueue_style(
            'wpwps-common',
            WPWPS_URL . 'assets/css/common.css',
            ['wpwps-fontawesome'],
            WPWPS_VERSION
        );

        // Common JS
        wp_enqueue_script(
            'wpwps-common',
            WPWPS_URL . 'assets/js/common.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-common', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-admin'),
            'current_time' => $this->currentTime,
            'current_user' => $this->currentUser
        ]);
    }

    private function enqueueStyle(string $name): void
    {
        if (isset($this->registeredAssets[$name])) {
            return;
        }

        wp_enqueue_style(
            "wpwps-{$name}",
            WPWPS_URL . "assets/css/{$name}.css",
            ['wpwps-common'],
            WPWPS_VERSION
        );

        $this->registeredAssets[$name] = true;
    }

    private function enqueueScript(string $name): void
    {
        if (isset($this->registeredAssets[$name])) {
            return;
        }

        $dependencies = ['wpwps-common'];
        
        if ($name === 'chart') {
            wp_enqueue_script(
                'wpwps-chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js',
                [],
                '3.7.0',
                true
            );
            $dependencies[] = 'wpwps-chartjs';
        }

        wp_enqueue_script(
            "wpwps-{$name}",
            WPWPS_URL . "assets/js/{$name}.js",
            $dependencies,
            WPWPS_VERSION,
            true
        );

        $this->registeredAssets[$name] = true;
    }

    private function getCurrentPage(): string
    {
        $page = $_GET['page'] ?? '';
        return str_replace('wpwps-', '', $page);
    }
}