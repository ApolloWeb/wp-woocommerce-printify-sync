<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Templates;

class BladeEngine {
    private $template_path;
    private $cache_path;
    private $cached_templates = [];
    private $data = [];
    
    // Blade-like directives
    private $directives = [
        'if' => '<?php if($1): ?>',
        'elseif' => '<?php elseif($1): ?>',
        'else' => '<?php else: ?>',
        'endif' => '<?php endif; ?>',
        'foreach' => '<?php foreach($1): ?>',
        'endforeach' => '<?php endforeach; ?>',
        'for' => '<?php for($1): ?>',
        'endfor' => '<?php endfor; ?>',
        'while' => '<?php while($1): ?>',
        'endwhile' => '<?php endwhile; ?>',
        'isset' => '<?php if(isset($1)): ?>',
        'endisset' => '<?php endif; ?>',
        'empty' => '<?php if(empty($1)): ?>',
        'endempty' => '<?php endif; ?>',
        'php' => '<?php $1; ?>',
        'include' => '<?php echo $this->render($1, $2 ?? []); ?>',
        'component' => '<?php echo $this->renderComponent($1, $2 ?? []); ?>',
    ];
    
    private $components = [];

    public function __construct(string $template_path, string $cache_path = null) {
        $this->template_path = rtrim($template_path, '/');
        $this->cache_path = $cache_path ?? WP_CONTENT_DIR . '/cache/wpwps/templates';
        
        if (!file_exists($this->cache_path)) {
            wp_mkdir_p($this->cache_path);
        }
    }
    
    public function share(string $key, $value): void {
        $this->data[$key] = $value;
    }

    public function render(string $template, array $data = []): string {
        $file = $this->compiledPath($template);
        
        // Merge data with shared data
        $merged_data = array_merge($this->data, $data);
        $merged_data['view'] = $this;
        
        // Extract variables for use in template
        extract($merged_data);
        
        // Start output buffering
        ob_start();
        include $file;
        return ob_get_clean();
    }
    
    public function registerComponent(string $name, callable $callback): void {
        $this->components[$name] = $callback;
    }
    
    public function renderComponent(string $name, array $data = []): string {
        if (!isset($this->components[$name])) {
            return "<!-- Component {$name} not found -->";
        }
        
        return call_user_func($this->components[$name], $data);
    }

    private function compiledPath(string $template): string {
        $template_file = $this->template_path . '/' . $template . '.php';
        
        if (!file_exists($template_file)) {
            throw new \Exception("Template file not found: {$template_file}");
        }
        
        // Get cache file path
        $cached_file = $this->cache_path . '/' . md5($template) . '.php';
        
        // Check if cache needs to be updated
        if (!file_exists($cached_file) || filemtime($template_file) > filemtime($cached_file) || defined('WP_DEBUG') && WP_DEBUG) {
            $content = file_get_contents($template_file);
            $compiled = $this->compile($content);
            file_put_contents($cached_file, $compiled);
        }
        
        return $cached_file;
    }

    private function compile(string $content): string {
        // Compile {{ }} expressions
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, "UTF-8"); ?>', $content);
        
        // Compile {!! !!} raw expressions
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $content);
        
        // Compile @directives
        foreach ($this->directives as $directive => $replacement) {
            $pattern = "/\@{$directive}(\s*\(.+?\)|\s)/";
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        return $content;
    }
    
    public function escape($value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
