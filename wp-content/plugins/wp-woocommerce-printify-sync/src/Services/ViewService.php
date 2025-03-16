<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Template\Engine;
use ApolloWeb\WPWooCommercePrintifySync\Context\SyncContext;

class ViewService
{
    private Engine $engine;
    private SyncContext $context;
    private array $shared = [];

    public function __construct(SyncContext $context)
    {
        $this->context = $context;
        $this->engine = new Engine(
            WPWPS_PLUGIN_DIR . '/views',
            WP_CONTENT_DIR . '/cache/wpwps'
        );
        
        $this->shareDefaultData();
    }

    public function render(string $view, array $data = []): string
    {
        return $this->engine->render($view, array_merge($this->shared, $data));
    }

    public function share(string $key, $value): void
    {
        $this->shared[$key] = $value;
    }

    private function shareDefaultData(): void
    {
        $this->share('context', $this->context);
        $this->share('currentUser', wp_get_current_user());
        $this->share('isProduction', !WP_DEBUG);
    }
}