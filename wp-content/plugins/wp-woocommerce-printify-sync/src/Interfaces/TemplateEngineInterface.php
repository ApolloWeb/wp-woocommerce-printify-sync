<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Interfaces;

interface TemplateEngineInterface
{
    public function render($template, $data = []);
}
