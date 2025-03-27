<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

abstract class ServiceProvider
{
    protected \ApolloWeb\WPWooCommercePrintifySync\Helpers\View $view;

    public function __construct(\ApolloWeb\WPWooCommercePrintifySync\Helpers\View $view)
    {
        $this->view = $view;
    }

    public function register(): void
    {
        // Register services
    }

    public function boot(): void
    {
        // Boot services
    }

    protected function addAction(string $hook, callable $callback, int $priority = 10, int $args = 1): void 
    {
        add_action($hook, $callback, $priority, $args);
    }

    protected function addFilter(string $hook, callable $callback, int $priority = 10, int $args = 1): void
    {
        add_filter($hook, $callback, $priority, $args);
    }

    protected function registerAdminMenu(string $pageTitle, string $menuTitle, string $capability, string $menuSlug, callable $callback): void 
    {
        add_action('admin_menu', function() use ($pageTitle, $menuTitle, $capability, $menuSlug, $callback) {
            add_menu_page(
                $pageTitle,
                $menuTitle,
                $capability,
                $menuSlug,
                $callback,
                'dashicons-store'
            );
        });
    }

    protected function verifyNonce(): bool 
    {
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'wpwps_ajax_nonce')) {
            wp_send_json_error('Invalid nonce');
            return false;
        }
        return true;
    }

    protected function registerAjaxEndpoint(string $action, callable $callback, bool $nopriv = false): void 
    {
        add_action('wp_ajax_' . $action, $callback);
        if ($nopriv) {
            add_action('wp_ajax_nopriv_' . $action, $callback);
        }
    }
}