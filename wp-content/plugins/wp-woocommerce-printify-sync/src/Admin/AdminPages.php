<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

// ...existing code...

class AdminPages
{
    private $templateEngine;
    private $pages = [];
    private $baseUrl;
    private $enqueue;
    private $container;

    public function __construct(TemplateEngineInterface $templateEngine, ServiceContainer $container)
    {
        $this->templateEngine = $templateEngine;
        $this->container = $container;
        $this->baseUrl = plugin_dir_url(dirname(dirname(__FILE__)));
        $this->enqueue = new Enqueue();
        $this->initializePages();
    }

    private function initializePages()
    {
        $this->pages = [
            new DashboardPage($this->templateEngine, $this->container),
            new SettingsPage($this->templateEngine, $this->container),
            new ProductsPage($this->templateEngine, $this->container),
            new OrdersPage($this->templateEngine, $this->container)
        ];
    }

    // ...existing code...
}
