<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * UI Component Registry
 */
class UIComponents {
    /**
     * @var array Registered components
     */
    private $components = [];
    
    /**
     * Initialize UI components
     */
    public function init(): void {
        $this->registerDefaultComponents();
        
        // Register component shortcode
        add_shortcode('wpps_component', [$this, 'renderComponentShortcode']);
    }
    
    /**
     * Register a component
     */
    public function register(string $name, callable $callback): void {
        $this->components[$name] = $callback;
    }
    
    /**
     * Render a component
     */
    public function render(string $name, array $props = []): string {
        if (!isset($this->components[$name])) {
            return "<!-- Component not found: {$name} -->";
        }
        
        try {
            return call_user_func($this->components[$name], $props);
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                return "<!-- Component error: {$e->getMessage()} -->";
            }
            return '';
        }
    }
    
    /**
     * Render component shortcode
     */
    public function renderComponentShortcode($atts, $content = null): string {
        $atts = shortcode_atts([
            'name' => '',
            'props' => '{}',
        ], $atts);
        
        if (empty($atts['name'])) {
            return '';
        }
        
        $props = json_decode($atts['props'], true) ?: [];
        return $this->render($atts['name'], $props);
    }
    
    /**
     * Register default components
     */
    private function registerDefaultComponents(): void {
        // Status badge component
        $this->register('status_badge', function($props) {
            $status = $props['status'] ?? 'default';
            $text = $props['text'] ?? $status;
            
            $classes = [
                'success' => 'bg-success',
                'error' => 'bg-danger',
                'warning' => 'bg-warning',
                'info' => 'bg-info',
                'default' => 'bg-secondary',
            ];
            
            $class = $classes[$status] ?? $classes['default'];
            return "<span class='badge {$class}'>{$text}</span>";
        });
        
        // Loading indicator
        $this->register('loading', function($props) {
            $size = $props['size'] ?? 'md';
            $text = $props['text'] ?? __('Loading...', 'wp-woocommerce-printify-sync');
            
            $sizes = [
                'sm' => 'spinner-border-sm',
                'md' => '',
                'lg' => 'spinner-border-lg',
            ];
            
            $spinner_class = $sizes[$size] ?? '';
            
            return "
                <div class='wpwps-loading-indicator d-flex align-items-center'>
                    <div class='spinner-border {$spinner_class} text-primary me-2' role='status'>
                        <span class='visually-hidden'>{$text}</span>
                    </div>
                    <span>{$text}</span>
                </div>
            ";
        });
        
        // Alert component
        $this->register('alert', function($props) {
            $type = $props['type'] ?? 'info';
            $message = $props['message'] ?? '';
            $dismissible = $props['dismissible'] ?? true;
            
            $dismissible_class = $dismissible ? 'alert-dismissible fade show' : '';
            $dismiss_button = $dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' : '';
            
            return "
                <div class='alert alert-{$type} {$dismissible_class}' role='alert'>
                    {$message}
                    {$dismiss_button}
                </div>
            ";
        });
    }
}
