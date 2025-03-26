<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use eftec\bladeone\BladeOne;

/**
 * Blade-like template engine for rendering admin pages
 */
class BladeTemplateEngine {
    /**
     * Path to templates directory
     */
    private $templatesDir;
    private $blade;
    private $cache_path;

    /**
     * Constructor
     */
    public function __construct() {
        $this->templatesDir = WPWPS_PATH . 'templates/';
        $this->cache_path = WPWPS_PATH . 'cache';
        
        // Create cache directory if it doesn't exist
        if (!file_exists($this->cache_path)) {
            wp_mkdir_p($this->cache_path);
        }

        // Use MODE_DEBUG in development, MODE_AUTO in production
        $mode = defined('WP_DEBUG') && WP_DEBUG ? BladeOne::MODE_DEBUG : BladeOne::MODE_AUTO;

        $this->blade = new BladeOne(
            WPWPS_PATH . 'templates',
            $this->cache_path,
            $mode
        );

        // Add WordPress translation function
        $this->blade->directive('__', function($expression) {
            return "<?php echo esc_html__($expression, 'wp-woocommerce-printify-sync'); ?>";
        });
    }

    /**
     * Render a template
     * 
     * @param string $template Template name
     * @param array $data Data to pass to the template
     * @return string Rendered template
     */
    public function render(string $template, array $data = []): string {
        // For debugging the issue with blank page
        error_log('Attempting to render template: ' . $template);
        
        $templatePath = $this->templatesDir . $template . '.blade.php';
        
        if (!file_exists($templatePath)) {
            error_log('Template file not found: ' . $templatePath);
            return 'Template not found: ' . $template;
        }
        
        ob_start();
        
        // Extract data to make it available to the template
        extract($data);
        
        // Include the template
        include $templatePath;
        
        $content = ob_get_clean();
        
        // Process @include directives
        $content = $this->processIncludes($content, $data);
        
        // For debugging - check output
        if (empty($content)) {
            error_log('Warning: Empty content generated for template ' . $template);
        } else {
            error_log('Template rendered successfully with length: ' . strlen($content));
        }
        
        return $content;
    }

    /**
     * Process @include directives in templates
     * 
     * @param string $content Template content
     * @param array $data Data to pass to includes
     * @return string Processed content
     */
    private function processIncludes(string $content, array $data): string {
        // Match @include('template.name')
        return preg_replace_callback('/@include\(\'([^\']+)\'\)/', function($matches) use ($data) {
            $includePath = $this->templatesDir . $matches[1] . '.blade.php';
            
            if (!file_exists($includePath)) {
                error_log('Include template not found: ' . $includePath);
                return '<!-- Include not found: ' . $matches[1] . ' -->';
            }
            
            ob_start();
            extract($data);
            include $includePath;
            return ob_get_clean();
        }, $content);
    }
}