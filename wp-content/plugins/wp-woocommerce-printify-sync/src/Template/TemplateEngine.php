<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Template;

class TemplateEngine
{
    private string $templateDir;
    private array $globalData = [];

    public function __construct(string $templateDir)
    {
        $this->templateDir = rtrim($templateDir, '/');
    }

    public function setGlobalData(array $data): void
    {
        $this->globalData = array_merge($this->globalData, $data);
    }

    public function render(string $template, array $data = []): string
    {
        $templateFile = $this->resolveTemplate($template);
        
        if (!file_exists($templateFile)) {
            throw new \RuntimeException(
                sprintf('Template file not found: %s', $template)
            );
        }

        // Merge global data with local data
        $data = array_merge($this->globalData, $data);
        
        // Start output buffering
        ob_start();
        
        // Extract variables for use in template
        extract($data, EXTR_SKIP);
        
        // Include the template file
        include $templateFile;
        
        // Return the buffered content
        return ob_get_clean();
    }

    public function partial(string $partial, array $data = []): string
    {
        return $this->render("partials/{$partial}", $data);
    }

    public function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    private function resolveTemplate(string $template): string
    {
        // Add .php extension if not present
        if (!preg_match('/\.php$/', $template)) {
            $template .= '.php';
        }

        return $this->templateDir . '/' . $template;
    }
}