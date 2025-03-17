<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Container\Container;

final class Plugin {
    private static ?Plugin $instance = null;
    private Container $container;
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->container = new Container();
        $this->initializeServices();
        $this->registerHooks();
    }
    
    private function initializeServices(): void {
        // Core Services
        $this->container->register('config', ConfigService::class);
        $this->container->register('logger', LogManager::class);
        $this->container->register('cache', CacheManager::class);
        
        // API Services
        $this->container->register('printify.api', PrintifyAPIClient::class);
        $this->container->register('currency.converter', CurrencyConverter::class);
        
        // Sync Services
        $this->container->register('product.sync', ProductSyncService::class);
        $this->container->register('order.sync', OrderSyncService::class);
        
        // Admin Services
        $this->container->register('admin.manager', AdminManager::class);
    }
    
    private function registerHooks(): void {
        add_action('init', [$this, 'init']);
        add_action('admin_init', [$this, 'adminInit']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }
}