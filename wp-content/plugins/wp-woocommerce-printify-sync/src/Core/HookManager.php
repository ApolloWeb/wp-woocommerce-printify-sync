<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class HookManager
{
    private $loader;
    
    public function __construct($loader)
    {
        $this->loader = $loader;
    }

    public function registerHooks($adminPages, $ajaxHandler)
    {
        // Admin
        $this->loader->addAction('admin_menu', $adminPages, 'registerMenus');
        $this->loader->addAction('admin_enqueue_scripts', $adminPages, 'enqueueAssets');

        // Ajax handlers
        $this->loader->addAction('wp_ajax_printify_sync', $ajaxHandler, 'handleAjax');
    }
}
