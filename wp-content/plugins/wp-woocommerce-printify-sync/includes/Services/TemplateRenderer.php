<?php
namespace WPWPS\Services;

use eftec\bladeone\BladeOne;

/**
 * Service for rendering templates using BladeOne
 */
class TemplateRenderer {
    /**
     * @var BladeOne The BladeOne instance
     */
    private $blade;

    /**
     * @var string The template directory
     */
    private $templateDir;

    /**
     * @var string The compiled templates directory
     */
    private $compiledDir;

    /**
     * @var bool Debug mode flag
     */
    private $debug;

    /**
     * TemplateRenderer constructor
     */
    public function __construct() {
        $this->templateDir = WPWPS_PLUGIN_PATH . 'templates';
        $this->compiledDir = WPWPS_PLUGIN_PATH . 'cache/templates';
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;
        
        // Create the cache directory if it doesn't exist
        if (!file_exists($this->compiledDir)) {
            wp_mkdir_p($this->compiledDir);
        }
        
        // Initialize BladeOne
        $this->blade = new BladeOne(
            $this->templateDir,
            $this->compiledDir,
            $this->debug ? BladeOne::MODE_DEBUG : BladeOne::MODE_AUTO
        );
        
        // Register custom directives
        $this->registerCustomDirectives();
    }

    /**
     * Register custom directives for BladeOne
     */
    private function registerCustomDirectives(): void {
        // Add WP nonce field directive
        $this->blade->directive('wpnonce', function ($expression) {
            return "<?php wp_nonce_field($expression); ?>";
        });
        
        // Add settings field directive
        $this->blade->directive('settings_fields', function ($expression) {
            return "<?php settings_fields($expression); ?>";
        });
        
        // Add do settings sections directive
        $this->blade->directive('do_settings_sections', function ($expression) {
            return "<?php do_settings_sections($expression); ?>";
        });
        
        // Add submit button directive
        $this->blade->directive('submit_button', function ($expression) {
            return "<?php submit_button($expression); ?>";
        });
    }

    /**
     * Render a template with data
     *
     * @param string $template Template name (without .blade.php extension)
     * @param array $data Data to pass to the template
     * @return string The rendered template
     */
    public function render(string $template, array $data = []): string {
        try {
            return $this->blade->run($template, $data);
        } catch (\Exception $e) {
            if ($this->debug) {
                return 'Template Error: ' . $e->getMessage();
            } else {
                error_log('Template Error: ' . $e->getMessage());
                return 'An error occurred while rendering the template.';
            }
        }
    }

    /**
     * Render a template directly to output
     *
     * @param string $template Template name (without .blade.php extension)
     * @param array $data Data to pass to the template
     * @return void
     */
    public function display(string $template, array $data = []): void {
        echo $this->render($template, $data);
    }

    /**
     * Add a global variable that will be available in all templates
     *
     * @param string $name Variable name
     * @param mixed $value Variable value
     * @return void
     */
    public function addGlobal(string $name, $value): void {
        $this->blade->share($name, $value);
    }
}