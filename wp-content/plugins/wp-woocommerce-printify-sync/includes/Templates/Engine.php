<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Templates;

class Engine {
    private $template_path;
    private $data = [];
    private $sections = [];

    public function __construct(string $template_path) {
        $this->template_path = $template_path;
        $this->data = [
            'assets_url' => WPPS_PUBLIC_URL,
            'admin_url' => admin_url()
        ];
    }

    public function render(string $template, array $data = []): string {
        $file = $this->template_path . $template . '.php';
        if (!file_exists($file)) {
            throw new \Exception("Template file not found: {$file}");
        }

        $this->data = array_merge($this->data, $data);
        extract($this->data);
        
        ob_start();
        include $file;
        return ob_get_clean();
    }

    public function insert(string $template, array $data = []): string {
        return $this->render($template, $data);
    }

    public function layout(string $template, array $data = []): void {
        $this->sections['layout'] = $template;
        $this->data = array_merge($this->data, $data);
    }

    public function section(string $name): void {
        ob_start();
    }

    public function end(): void {
        $this->sections[array_pop($this->section_stack)] = ob_get_clean();
    }

    public function yield(string $section): string {
        return $this->sections[$section] ?? '';
    }
}
