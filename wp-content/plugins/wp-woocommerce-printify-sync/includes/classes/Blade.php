<?php

namespace ApolloWeb\WpWooCommercePrintifySync;

use Illuminate\View\Factory;
use Illuminate\View\ViewServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;

class Blade
{
    protected $viewFactory;

    public function __construct()
    {
        $container = new Container;
        $container->singleton('files', function () {
            return new Filesystem;
        });

        $container->singleton('view.finder', function ($app) {
            $paths = [WPWPSP_PLUGIN_DIR . 'templates'];
            return new FileViewFinder($app['files'], $paths);
        });

        $container->singleton('view.engine.resolver', function () {
            return new \Illuminate\View\Engines\EngineResolver;
        });

        $viewServiceProvider = new ViewServiceProvider($container);
        $viewServiceProvider->register();

        $this->viewFactory = $container->make(Factory::class);
    }

    public function render($view, $data = [])
    {
        return $this->viewFactory->make($view, $data)->render();
    }
}