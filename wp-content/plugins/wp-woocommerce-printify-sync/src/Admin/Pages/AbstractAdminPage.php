<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\TemplateEngineInterface;
use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;

abstract class AbstractAdminPage
{
    protected $templateEngine;
    protected $container;
    public $slug;
    public $pageTitle;
    public $menuTitle;

    public function __construct(TemplateEngineInterface $templateEngine, ServiceContainer $container = null)
    {
        $this->templateEngine = $templateEngine;
        $this->container = $container;
    }

    abstract public function render();
    abstract public function getRequiredAssets(): array;
}
