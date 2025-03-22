<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Container;

class AdminManager {
    private $container;
    private $menu;
    private $assets;

    public function __construct(Container $container) {
        $this->container = $container;
        $this->menu = new AdminMenu($container->get('template_engine'));
        $this->assets = new AdminAssets();
    }

    public function init(): void {
        $this->menu->init();
        $this->assets->init();
    }
}
