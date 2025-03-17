<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class AdminManager
{
    use TimeStampTrait;

    private const MENU_SLUG = 'printify-sync';
    private const CAPABILITY = 'manage_woocommerce';

    private AssetManager $assetManager;
    private LoggerInterface $logger;
    private ConfigService $config;
    private WebhookMonitor $webhookMonitor;
    private APIHealthCheck $apiHealth;

    public function __construct(
        AssetManager $assetManager,
        LoggerInterface $logger,
        ConfigService $config,
        WebhookMonitor $webhookMonitor,
        APIHealthCheck $apiHealth
    ) {
        $this->assetManager = $assetManager;
        $this->logger = $logger;
        $this->config = $config;
        $this->webhookMonitor = $webhookMonitor;
        $this->apiHealth = $apiHealth;
    }

    public function initialize(): void
    {
        add_action('admin_menu', [$this, 'registerMenus']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_get_health_status', [$this, 'getHealthStatus']);
        add_action('wp_ajax_wpwps_get_webhook_stats', [$this, 'getWebhookStats']);
    }

    public function registerMenus(): void
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderDashboard'],
            'dashicons-synchronization',
            56
        );

        $this->addSubMenuPages();
    }

    private function addSubMenuPages(): void
    {
        $subPages = [
            'dashboard' => [
                'title' => __('Dashboard', 'wp-woocommerce-printify-sync'),
                'callback' => 'renderDashboard'
            ],
            'webhooks' => [
                'title' => __('Webhooks', 'wp-woocommerce-printify-sync'),
                'callback' => 'renderWebhooks'
            ],
            'logs' => [
                'title' => __('Logs', 'wp-woocommerce-printify-sync'),
                'callback' => 'renderLogs'
            ],
            'settings' => [
                'title' => __('Settings', 'wp-woocommerce-printify-sync'),
                'callback' => 'renderSettings'
            ]
        ];

        foreach ($subPages as $slug => $page) {
            add_submenu_page(
                self::MENU_SLUG,
                $page['title'],
                $page['title'],
                self::CAPABILITY,
                self::MENU_SLUG . '-' . $slug,
                [$this, $page['callback']]
            );
        }
    }

    public function renderDashboard(): void
    {
        $data = [
            'api_health' => $this->apiHealth->getStatus(),
            'webhook_stats' => $this->webhookMonitor->getStats(),
            'sync_status' => $this->getSyncStatus(),
            'recent_activity' => $this->getRecentActivity()
        ];

        $this->renderTemplate('dashboard', $data);
    }

    public function renderWebhooks(): void
    {
        $data = [
            'webhooks' => $this->webhookMonitor->getWebhooks(),
            'stats' => $this->webhookMonitor->getDetailedStats(),
            'health' => $this->webhookMonitor->getHealth()
        ];

        $this->renderTemplate('webhooks', $data);
    }

    public function renderLogs(): void
    {
        $page = $_GET['page'] ?? 1;
        $perPage = 50;

        $data = [
            'logs' => $this->logger->getPaginatedLogs($page, $perPage),
            'total' => $this->logger->getTotalLogs(),
            'page' => $page,
            'per_page' => $perPage
        ];

        $this->renderTemplate('logs', $data);
    }

    public function renderSettings(): void
    {
        $data = [
            'settings' => $this->config->all(),
            'api_status' => $this->apiHealth->getStatus(),
            'webhook_config' => $this->webhookMonitor->getConfig()
        ];

        $this->renderTemplate('settings', $data);
    }

    private function renderTemplate(string $template, array $data = []): void
    {
        $templatePath = WPWPS_PLUGIN_DIR . '/templates/admin/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            wp_die(sprintf(
                __('Template %s not found', 'wp-woocommerce-printify-sync'),
                $template
            ));
        }

        extract($data);
        include $templatePath;
    }

    private function getSyncStatus(): array
    {
        global $wpdb;

        return [
            'last_sync' => get_option('wpwps_last_sync'),
            'products_synced' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                WHERE meta_key = '_printify_id'"
            ),
            'orders_synced' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                WHERE meta_key = '_printify_order_id'"
            ),
            'sync_errors' => $this->logger->getRecentErrors('sync', 24)
        ];
    }

    private function getRecentActivity(): array
    {
        return $this->logger->getRecentActivity(10);
    }

    public function getHealthStatus(): void
    {
        check_ajax_referer('wpwps_admin');

        wp_send_json([
            'api' => $this->apiHealth->getStatus(),
            'webhooks' => $this->webhookMonitor->getHealth(),
            'sync' => $this->getSyncStatus(),
            'timestamp' => $this->getCurrentTime()
        ]);
    }

    public function getWebhookStats(): void
    {
        check_ajax_referer('wpwps_admin');

        wp_send_json([
            'stats' => $this->webhookMonitor->getDetailedStats(),
            'health' => $this->webhookMonitor->getHealth(),
            'timestamp' => $this->getCurrentTime()
        ]);
    }
}