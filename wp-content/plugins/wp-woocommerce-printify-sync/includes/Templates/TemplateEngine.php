<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Templates;

class TemplateEngine
{
    /**
     * Template directory
     *
     * @var string
     */
    private string $templateDir;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->templateDir = WPWPS_PLUGIN_DIR . 'templates/';
    }
    
    /**
     * Render a template
     *
     * @param string $template Template name (without .php extension)
     * @param array $data Data to pass to the template
     * @return void
     */
    public function render(string $template, array $data = []): void
    {
        $templatePath = $this->templateDir . 'wpwps-' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            wp_die(sprintf('Template "%s" not found.', $template));
            return;
        }
        
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the template
        include $templatePath;
        
        // End output buffering and echo the content
        echo ob_get_clean();
    }
    
    /**
     * Render a section or partial template
     * 
     * @param string $section Section/partial name
     * @param array $data Data to pass to the section
     * @return void
     */
    public function section(string $section, array $data = []): void
    {
        $templatePath = $this->templateDir . 'wpwps-partials/' . $section . '.php';
        
        if (!file_exists($templatePath)) {
            wp_die(sprintf('Partial template "%s" not found.', $section));
            return;
        }
        
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the template
        include $templatePath;
        
        // End output buffering and echo the content
        echo ob_get_clean();
    }
    
    /**
     * Get a rendered template as a string
     * 
     * @param string $template Template name
     * @param array $data Data to pass to the template
     * @return string Rendered template
     */
    public function get(string $template, array $data = []): string
    {
        ob_start();
        $this->render($template, $data);
        return ob_get_clean();
    }
}
