<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\TemplateEngineInterface;

class TemplateEngine implements TemplateEngineInterface
{
    private $templatePath;

    public function __construct()
    {
        $this->templatePath = plugin_dir_path(dirname(__DIR__)) . 'templates/';
    }

    public function render($template, $data = [])
    {
        $templateFile = $this->templatePath . $template;
        
        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template file not found: {$template}");
        }

        if (!empty($data)) {
            extract($data);
        }

        ob_start();
        include $templateFile;
        return ob_get_clean();
    }
}
