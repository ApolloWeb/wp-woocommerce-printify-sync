<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Template;

class Engine {
    private string $layoutsPath;
    private string $viewsPath;
    private array $data = [];
    private array $sections = [];

    public function __construct(string $layoutsPath, string $viewsPath) {
        $this->layoutsPath = $layoutsPath;
        $this->viewsPath = $viewsPath;
    }

    public function render(string $view, array $data = []): string {
        $this->data = $data;
        $this->sections = [];
        
        // Log detailed debug info
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Template Engine: Rendering view ' . $view);
            error_log('View path: ' . $this->viewsPath . '/' . ltrim($view, '/') . '.php');
        }
        
        // Check if view file exists
        $viewPath = $this->viewsPath . '/' . ltrim($view, '/') . '.php';
        if (!file_exists($viewPath)) {
            error_log('View file not found: ' . $viewPath);
            return $this->renderFallback("View file not found: {$viewPath}");
        }
        
        // Capture the output of the view
        ob_start();
        
        try {
            // Extract data to create variables
            extract($this->data);
            
            // Include the view file
            include $viewPath;
            
            // Store all sections captured during view rendering
            $output = ob_get_clean();
            
            // Check if there's a layout
            if (isset($this->data['__layout'])) {
                $layoutName = str_replace('layouts/', '', $this->data['__layout']);
                $layoutPath = rtrim($this->layoutsPath, '/') . '/' . ltrim($layoutName, '/') . '.php';
                
                if (!file_exists($layoutPath)) {
                    error_log('Layout file not found: ' . $layoutPath);
                    return $this->renderFallback("Layout file not found: {$layoutPath}");
                }
                
                // Now render the layout with the content from the view
                $this->data['content'] = $output;
                
                // Start output buffer for layout
                ob_start();
                extract($this->data);
                include $layoutPath;
                return ob_get_clean();
            }
            
            // If no layout, just return the view output
            return $output;
            
        } catch (\Throwable $e) {
            // Clean up any output buffer if an error occurred
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            error_log('Template Error: ' . $e->getMessage());
            error_log('In file: ' . $e->getFile() . ' on line ' . $e->getLine());
            
            return $this->renderFallback($e->getMessage());
        }
    }

    /**
     * Create a fallback error display
     */
    private function renderFallback(string $errorMessage): string {
        ob_start();
        ?>
        <div class="wpwps-error" style="background: #fee; border-left: 4px solid #c00; padding: 15px; margin: 15px 0;">
            <h3 style="margin-top: 0; color: #c00;">Template Error</h3>
            <p><?php echo esc_html($errorMessage); ?></p>
            <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                <div style="background: #f8f8f8; padding: 10px; margin-top: 10px; font-family: monospace; font-size: 12px;">
                    <strong>Debug Information:</strong>
                    <ul>
                        <li>View Path: <?php echo esc_html($this->viewsPath); ?></li>
                        <li>Layout Path: <?php echo esc_html($this->layoutsPath); ?></li>
                        <li>Request: <?php echo esc_html($_SERVER['REQUEST_URI'] ?? 'unknown'); ?></li>
                        <li>Time: <?php echo esc_html(current_time('mysql')); ?></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function extend(string $layout): void {
        $this->data['__layout'] = $layout;
    }

    public function section(string $name): void {
        ob_start();
    }

    public function endSection(string $name): void {
        // Store the section content from the buffer
        $this->sections[$name] = ob_get_clean();
        $this->data['sections'] = $this->sections;
    }

    public function yield(string $name): void {
        if (isset($this->data[$name])) {
            echo $this->data[$name];
        } elseif (isset($this->sections[$name])) {
            echo $this->sections[$name];
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo "<div style='background: #ffc; border-left: 4px solid #f90; padding: 10px; margin: 10px 0;'>";
                echo "No content for section: " . esc_html($name);
                echo "</div>";
            }
        }
    }

    public function component(string $name, array $data = []): void {
        $componentFile = dirname($this->viewsPath) . '/components/' . $name . '.php';
        
        if (!file_exists($componentFile)) {
            error_log('Component file not found: ' . $componentFile);
            echo "<div style='color: red;'>Component not found: " . esc_html($componentFile) . "</div>";
            return;
        }
        
        // Merge component data with global data
        $mergedData = array_merge($this->data, $data);
        extract($mergedData);
        
        include $componentFile;
    }

    public function partial(string $name, array $data = []): void {
        $partialPath = dirname($this->viewsPath) . '/partials/' . $name . '.php';
        
        if (!file_exists($partialPath)) {
            error_log("Partial not found: $partialPath");
            echo "<div class='error'>Partial not found: " . esc_html($name) . "</div>";
            return;
        }
        
        // Merge with main data but prioritize partial data
        $mergedData = array_merge($this->data, $data);
        extract($mergedData);
        
        include $partialPath;
    }
    
    /**
     * Get direct debug output for troubleshooting
     */
    public function getDebugInfo(): string {
        return TemplateDebugger::getDebugInfo($this->data);
    }
}
