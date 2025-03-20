<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Interfaces;

interface TemplateEngineInterface 
{
    public function render($template, $data = []);
}
