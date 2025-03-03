<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Abstracts;

abstract class AbstractWidget
{
    abstract public static function render();

    protected static function getTemplate($template, $data = [])
    {
        extract($data);
        include plugin_dir_path(__FILE__) . '../../templates/admin/' . $template . '.php';
    }
}