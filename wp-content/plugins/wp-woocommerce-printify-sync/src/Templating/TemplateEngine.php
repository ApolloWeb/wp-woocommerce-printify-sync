<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Templating;

use League\Plates\Engine;

class TemplateEngine {
    private $engine;

    public function __construct() {
        $this->engine = new Engine(WPPS_TEMPLATES_PATH);
        
        // Add folders for partials
        $this->engine->addFolder('dashboard', WPPS_TEMPLATES_PATH . 'partials/dashboard');
        $this->engine->addFolder('settings', WPPS_TEMPLATES_PATH . 'partials/settings');
        $this->engine->addFolder('products', WPPS_TEMPLATES_PATH . 'partials/products');
        $this->engine->addFolder('orders', WPPS_TEMPLATES_PATH . 'partials/orders');
        $this->engine->addFolder('shipping', WPPS_TEMPLATES_PATH . 'partials/shipping');
        $this->engine->addFolder('tickets', WPPS_TEMPLATES_PATH . 'partials/tickets');

        $this->engine->addData([
            'assets_url' => WPPS_ASSETS_URL,
            'admin_url' => WPPS_ADMIN_URL
        ]);
    }

    public function render(string $template, array $data = []): string {
        return $this->engine->render($template, $data);
    }
}
