<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Interfaces;

interface HookLoaderInterface
{
    public function addAction($hook, $component, $callback);
    public function addFilter($hook, $component, $callback);
    public function run();
}
