<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class Template {
    private string $template_path;
    private array $global_data = [];

    public function __construct() {
        $this->template_path = plugin_dir_path(__DIR__) . 'templates/';
    }

    /**
     * Share data across all templates
     */
    public function share(array $data): void {
        $this->global_data = array_merge($this->global_data, $data);
    }

    /**
     * Render a partial template
     */
    public function partial(string $name, array $data = []): string {
        $partial_file = $this->template_path . 'partials/' . $name . '.php';
        return $this->render($partial_file, $data);
    }

    /**
     * Include a partial template inline
     */
    public function include(string $name, array $data = []): void {
        echo $this->partial($name, $data);
    }

    /**
     * Renders a template file with PHP variables
     */
    public function render(string $template_file, array $data = []): string {
        if (!file_exists($template_file)) {
            return '';
        }

        // Merge global data with local data
        $data = array_merge($this->global_data, $data);
        
        // Extract variables for use in template
        extract($data, EXTR_SKIP);
        
        ob_start();
        include $template_file;
        return ob_get_clean();
    }

    /**
     * Escape HTML by default
     */
    public function e(string $value): string {
        return esc_html($value);
    }
}
