<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Plugin
{
    private static $instance = null;
    private $container;

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void
    {
        $this->initContainer();
        $this->registerHooks();
        $this->loadTextDomain();
    }

    private function initContainer(): void
    {
        $this->container = new Container();
        $this->container->register(new AdminServiceProvider());
        $this->container->register(new ApiServiceProvider());
    }

    private function registerHooks(): void
    {
        add_action('admin_menu', [$this->container->get(Admin::class), 'registerMenuPages']);
        add_action('admin_enqueue_scripts', [$this->container->get(Admin::class), 'enqueueAssets']);
    }

    private function loadTextDomain(): void
    {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(plugin_basename(WPWPS_PLUGIN_DIR)) . '/languages/'
        );
    }
}
