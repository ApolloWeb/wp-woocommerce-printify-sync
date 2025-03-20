<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\HookLoaderInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\TemplateEngineInterface;
use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;

class Plugin
{
    private $loader;
    private $templateEngine;
    private $container;
    private $hookManager;

    public function __construct(
        HookLoaderInterface $loader,
        TemplateEngineInterface $templateEngine,
        ServiceContainer $container
    ) {
        $this->loader = $loader;
        $this->templateEngine = $templateEngine;
        $this->container = $container;
        $this->hookManager = new Core\HookManager($this->loader);
        
        $this->defineHooks();
    }

    private function defineHooks()
    {
        $adminPages = new Admin\AdminPages($this->templateEngine, $this->container);
        $ajaxHandler = new Ajax\AjaxHandler($this->container);
        
        $this->hookManager->registerHooks($adminPages, $ajaxHandler);
    }

    public function run()
    {
        $this->loader->run();
    }
}
