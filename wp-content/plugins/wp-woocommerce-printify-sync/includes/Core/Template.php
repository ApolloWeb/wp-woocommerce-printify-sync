<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Template {
    private $template_path;
    private $cached_templates = [];
    private $shared_data = [];

    public function __construct() {
        $this->template_path = WPPS_PATH . 'templates/';
    }

    public function render(string $name, array $data = []): string {
        $template_file = $this->findTemplate($name);
        
        if (!$template_file) {
            throw new \Exception("Template {$name} not found");
        }
        
        // Merge shared data with template-specific data
        $data = array_merge($this->shared_data, $data);
        
        // Extract variables for use in template
        extract($data);
        
        // Start output buffering
        ob_start();
        
        include $template_file;
        
        return ob_get_clean();
    }

    public function share(string $key, $value): void {
        $this->shared_data[$key] = $value;
    }

    public function exists(string $name): bool {
        return (bool) $this->findTemplate($name);
    }

    private function findTemplate(string $name): ?string {
        // Check cache first
        if (isset($this->cached_templates[$name])) {
            return $this->cached_templates[$name];
        }
        
        // Convert dot notation to directory structure
        $file = str_replace('.', '/', $name) . '.php';
        $path = $this->template_path . $file;
        
        if (file_exists($path)) {
            $this->cached_templates[$name] = $path;
            return $path;
        }
        
        return null;
    }
    
    public function section(string $name, callable $callback): void {
        ob_start();
        $callback();
        $this->shared_data['sections'][$name] = ob_get_clean();
    }
    
    public function yield(string $name): string {
        return $this->shared_data['sections'][$name] ?? '';
    }
    
    public function include(string $name, array $data = []): string {
        return $this->render($name, $data);
    }
    
    public function escape($value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
