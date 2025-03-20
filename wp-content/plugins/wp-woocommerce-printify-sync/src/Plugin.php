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
        
        // Initialize the order item display hooks
        new WooCommerce\OrderItemDisplay();
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

    /**
     * Check if HPOS is active in WooCommerce
     *
     * @return bool
     */
    public function isHPOSActive(): bool
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        
        return false;
    }
    
    /**
     * Get admin URL for editing orders (HPOS compatible)
     *
     * @param int $orderId
     * @return string
     */
    public function getOrderEditUrl(int $orderId): string
    {
        if ($this->isHPOSActive()) {
            return admin_url("admin.php?page=wc-orders&action=edit&id={$orderId}");
        }
        
        return get_edit_post_link($orderId, 'raw');
    }
}
