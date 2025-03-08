<?php

namespace ApolloWeb\WpWooCommercePrintifySync\Abstracts;

abstract class BaseController
{
    /**
     * Render a view using Blade templating.
     *
     * @param string $view
     * @param array $data
     * @return string
     */
    protected function renderView($view, $data = [])
    {
        $blade = new \ApolloWeb\WpWooCommercePrintifySync\Blade();
        return $blade->render($view, $data);
    }
}