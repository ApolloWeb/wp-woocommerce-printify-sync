<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Core\LogManager;

class LogsPage
{
    private LogManager $logManager;

    public function __construct()
    {
        $this->logManager = new LogManager();
    }

    public function register(): void
    {
        add_submenu_page(
            'wpwps-dashboard',
            __('System Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-logs',
            [$this, 'render']
        );

        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void
    {
        $action = $_GET['action'] ?? 'list';
        $filename = $_GET['file'] ?? '';

        switch ($action) {
            case 'view':
                $this->renderLogView($filename);
                break;
            default:
                $this->renderLogList();
                break;
        }
    }

    private function renderLogList(): void
    {
        $logFiles = $this->logManager->getLogFiles();
        echo View::render('wpwps-logs', [
            'title' => __('System Logs', 'wp-woocommerce-printify-sync'),
            'logs' => $logFiles
        ]);
    }

    private function renderLogView(string $filename): void
    {
        $content = $this->logManager->getLogContent($filename);
        if ($content === null) {
            wp_redirect(admin_url('admin.php?page=wpwps-logs'));
            exit;
        }

        echo View::render('wpwps-log-view', [
            'title' => __('View Log', 'wp-woocommerce-printify-sync'),
            'filename' => $filename,
            'content' => $content
        ]);
    }

    public function enqueueAssets(): void
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpwps-logs') {
            return;
        }

        wp_enqueue_style('wpwps-logs', WPWPS_URL . 'assets/css/wpwps-logs.css', [], WPWPS_VERSION);
        wp_enqueue_script('wpwps-logs', WPWPS_URL . 'assets/js/wpwps-logs.js', ['jquery'], WPWPS_VERSION, true);
    }
}