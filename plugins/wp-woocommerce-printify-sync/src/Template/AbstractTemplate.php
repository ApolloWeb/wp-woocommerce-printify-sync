<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Template;

abstract class AbstractTemplate implements TemplateInterface
{
    protected string $templatePath;
    
    public function __construct(string $templatePath)
    {
        $this->templatePath = $templatePath;
    }

    public function render(array $data = []): string
    {
        if (!file_exists($this->templatePath)) {
            throw new \RuntimeException(
                sprintf('Template file not found: %s', $this->templatePath)
            );
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $this->templatePath;
        return ob_get_clean();
    }

    public function getPath(): string
    {
        return $this->templatePath;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    protected function partial(string $name, array $data = []): string
    {
        $path = $this->resolvePartialPath($name);
        return (new class($path) extends AbstractTemplate {})
            ->render($data);
    }

    private function resolvePartialPath(string $name): string
    {
        $dir = dirname($this->templatePath);
        return "{$dir}/partials/{$name}.php";
    }
}