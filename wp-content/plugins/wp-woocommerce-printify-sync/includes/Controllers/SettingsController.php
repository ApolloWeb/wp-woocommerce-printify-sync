<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Templates;

abstract class AdminView {
    protected $template_dir;
    protected $data = [];

    public function __construct() {
        $this->template_dir = WPPS_PATH . '/admin/partials';
    }

    protected function render(string $template, array $data = []): string {
        $this->data = array_merge($this->data, $data);
        $file = $this->template_dir . '/' . $template . '.php';

        if (!file_exists($file)) {
            throw new \Exception("Template file not found: {$file}");
        }

        ob_start();
        extract($this->data);
        include $file;
        return ob_get_clean();
    }

    protected function partial(string $name, array $data = []): string {
        return $this->render('partials/' . $name, $data);
    }

    protected function escape($value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
