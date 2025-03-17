<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Template;

class Engine
{
    private string $templatePath;
    private array $data = [];

    public function __construct()
    {
        $this->templatePath = WPWPS_PLUGIN_DIR . '/templates';
    }

    public function render(string $template, array $data = []): string
    {
        $this->data = $data;
        $file = $this->resolvePath($template);

        ob_start();
        $this->renderTemplate($file);
        return ob_get_clean();
    }

    private function renderTemplate(string $file): void
    {
        extract($this->data);
        include $file;
    }

    public function include(string $partial, array $data = []): void
    {
        echo $this->render($partial, array_merge($this->data, $data));
    }

    private function resolvePath(string $template): string
    {
        $file = $this->templatePath . '/' . $template . '.php';
        
        if (!file_exists($file)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        return $file;
    }
}