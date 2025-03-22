<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Templates;

abstract class View {
    protected $template;
    protected $data = [];

    public function __construct(string $template) {
        $this->template = $template;
    }

    public function with(string $key, $value): self {
        $this->data[$key] = $value;
        return $this;
    }

    public function render(): string {
        extract($this->data);
        ob_start();
        include WPPS_TEMPLATES_PATH . $this->template . '.php';
        return ob_get_clean();
    }
}
