<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Context\SyncContext;
use ApolloWeb\WPWooCommercePrintifySync\Services\SyncService;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class AdminInterface
{
    private SyncService $syncService;
    private LoggerInterface $logger;
    private SyncContext $context;

    public function __construct(
        SyncService $syncService,
        LoggerInterface $logger,
        SyncContext $context
    ) {
        $this->syncService = $syncService;
        $this->logger = $logger;
        $this->context = $context;
    }

    public function registerMenuPages(): void
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-sync',
            56
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettings']
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-logs',
            [$this, 'renderLogs']
        );
    }

    public function enqueueAssets(): void
    {
        $screen = get_current_screen();
        if (strpos($screen->id, 'wpwps') === false) {
            return;
        }

        wp_enqueue_style(
            'wpwps-admin',
            plugins_url('assets/css/admin.css', WPWPS_PLUGIN_FILE),
            [],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-admin',
            plugins_url('assets/js/admin.js', WPWPS_PLUGIN_FILE),
            ['jquery'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-admin', 'wpwps', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-admin'),
            'i18n' => [
                'confirmSync' => __('Are you sure you want to start a full sync?', 'wp-woocommerce-printify-sync'),
                'syncInProgress' => __('Sync in progress...', 'wp-woocommerce-printify-sync')
            ]
        ]);
    }

    public function renderDashboard(): void
    {
        $this->renderTemplate('dashboard', [
            'syncStatus' => $this->syncService->getCurrentSyncStatus(),
            'stats' => $this->syncService->getStats(),
            'currentTime' => $this->context->getCurrentTime()
        ]);
    }

    public function renderSettings(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('wpwps_settings')) {
            $this->updateSettings($_POST);
        }

        $this->renderTemplate('settings', [
            'settings' => $this->getSettings(),
            'currentTime' => $this->context->getCurrentTime()
        ]);
    }

    public function renderLogs(): void
    {
        $this->renderTemplate('logs', [
            'logs' => $this->logger->getRecentLogs(100),
            'currentTime' => $this->context->getCurrentTime()
        ]);
    }

    private function renderTemplate(string $template, array $data = []): void
    {
        extract($data);
        include WPWPS_PLUGIN_DIR . "/templates/admin/{$template}.php";
    }

    private function getSettings(): array
    {
        return [
            'api_key' => get_option('wpwps_api_key'),
            'shop_id' => get_option('wpwps_shop_id'),
            'webhook_secret' => get_option('wpwps_webhook_secret'),
            'sync_batch_size' => get_option('wpwps_sync_batch_size', 10),
            'image_optimization' => get_option('wpwps_image_optimization', 'on'),
            'enable_webp' => get_option('wpwps_enable_webp', 'on'),
            'log_level' => get_option('wpwps_log_level', 'info')
        ];
    }

    private function updateSettings(array $data): void
    {
        $settings = [
            'wpwps_api_key' => sanitize_text_field($data['api_key'] ?? ''),
            'wpwps_shop_id' => sanitize_text_field($data['shop_id'] ?? ''),
            'wpwps_sync_batch_size' => absint($data['sync_batch_size'] ?? 10),
            'wpwps_image_optimization' => $data['image_optimization'] ?? 'off',
            'wpwps_enable_webp' => $data['enable_webp'] ?? 'off',
            'wpwps_log_level' => sanitize_text_field($data['log_level'] ?? 'info')
        ];

        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }

        $this->logger->info('Settings updated', [
            'user' => $this->context->getCurrentUser()
        ]);
    }
}