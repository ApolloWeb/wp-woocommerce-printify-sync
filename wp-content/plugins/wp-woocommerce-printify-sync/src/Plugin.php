<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// ...existing code...

    private function defineHooks()
    {
        $adminPages = new Admin\AdminPages($this->templateEngine, $this->container);
        $ajaxHandler = new Ajax\AjaxHandler($this->container);
        
        $this->hookManager->registerHooks($adminPages, $ajaxHandler);
    }

    // ...existing code...
