<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Webhooks\WebhookHandler;

class WebhookProvider implements ServiceProvider
{
    public function register(): void
    {
        add_action('init', [$this, 'registerWebhookEndpoint']);
        add_filter('query_vars', [$this, 'registerQueryVars']);
        add_action('parse_request', [$this, 'handleWebhook']);
    }

    public function registerWebhookEndpoint(): void
    {
        add_rewrite_rule(
            '^wpwps/webhook/?$',
            'index.php?wpwps_webhook=1',
            'top'
        );
        
        // Flush rewrite rules only if needed
        if (get_option('wpwps_flush_rules', false)) {
            flush_rewrite_rules();
            update_option('wpwps_flush_rules', false);
        }
    }

    public function registerQueryVars(array $vars): array
    {
        $vars[] = 'wpwps_webhook';
        return $vars;
    }

    public function handleWebhook(): void
    {
        global $wp;
        
        if (!isset($wp->query_vars['wpwps_webhook'])) {
            return;
        }

        // Prevent caching of webhook requests
        nocache_headers();

        // Handle the webhook
        $handler = new WebhookHandler();
        $handler->handle();
    }
}