<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Page;

use ApolloWeb\WPWooCommercePrintifySync\Service\LogService;

class LogViewerPage extends AbstractAdminPage
{
    private LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function getTitle(): string
    {
        return __('Log Viewer', 'wp-woocommerce-printify-sync');
    }

    public function getMenuTitle(): string
    {
        $errorCount = $this->logService->getErrorCount();
        return sprintf(
            __('Logs %s', 'wp-woocommerce-printify-sync'),
            $errorCount ? "<span class='awaiting-mod'>{$errorCount}</span>" : ''
        );
    }

    public function getCapability(): string
    {
        return 'manage_options';
    }

    public function getMenuSlug(): string
    {
        return 'wpwps-logs';
    }

    public function register(): void
    {
        parent::register();
        add_action('wp_ajax_wpwps_clear_logs', [$this, 'handleClearLogs']);
        add_action('wp_ajax_wpwps_export_logs', [$this, 'handleExportLogs']);
    }

    public function render(): void
    {
        if (!current_user_can($this->getCapability())) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $currentType = sanitize_text_field($_GET['type'] ?? 'all');
        $currentPage = max(1, (int)($_GET['paged'] ?? 1));
        $search = sanitize_text_field($_GET['s'] ?? '');

        $logsList = $this->logService->getLogs([
            'type' => $currentType,
            'page' => $currentPage,
            'search' => $search,
            'per_page' => 50
        ]);

        $this->renderTemplate('logs', [
            'logs' => $logsList['logs'],
            'pagination' => $logsList['pagination'],
            'stats' => $this->logService->getStats(),
            'types' => $this->logService->getTypes(),
            'currentType' => $currentType,
            'search' => $search
        ]);
    }

    public function handleClearLogs(): void
    {
        check_ajax_referer('wpwps_logs', 'nonce');

        if (!current_user_can($this->getCapability())) {
            wp_send_json_error(['message' => __('Permission denied.', 'wp-woocommerce-printify-sync')]);
        }

        try {
            $this->logService->clearLogs();
            wp_send_json_success([
                'message' => __('Logs cleared successfully!', 'wp-woocommerce-printify-sync')
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handleExportLogs(): void
    {
        check_ajax_referer('wpwps_logs', 'nonce');

        if (!current_user_can($this->getCapability())) {
            wp_send_json_error(['message' => __('Permission denied.', 'wp-woocommerce-printify-sync')]);
        }

        try {
            $url = $this->logService->exportLogs();
            wp_send_json_success([
                'message' => __('Logs exported successfully!', 'wp-woocommerce-printify-sync'),
                'download_url' => $url
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}