<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

use eftec\bladeone\BladeOne;

class View {
    private BladeOne $blade;

    public function __construct(string $viewPath, string $cachePath) 
    {
        $this->blade = new BladeOne($viewPath, $cachePath, BladeOne::MODE_DEBUG);
        $this->setupDefaults();
    }

    private function setupDefaults(): void 
    {
        $this->blade->setBaseUrl(admin_url());
        $this->blade->setAuth(wp_get_current_user()->user_login);
        $this->blade->share('plugin_url', plugins_url('', dirname(__DIR__)));
    }

    public function render(string $view, array $data = []): string 
    {
        try {
            return $this->blade->run($view, $data);
        } catch (\Exception $e) {
            // Log error and show admin notice
            error_log('WPWPS Template Error: ' . $e->getMessage());
            return '<div class="notice notice-error"><p>Error rendering template: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    public function getBlade(): BladeOne 
    {
        return $this->blade;
    }
}