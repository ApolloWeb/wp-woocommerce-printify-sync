<?php
/**
 * View handler class with Blade-style templating
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Class View
 */
class View
{
    /**
     * Cache of compiled templates
     *
     * @var array
     */
    private static array $compiledCache = [];
    
    /**
     * Compile and render a view
     *
     * @param string $template The template name
     * @param array  $data     The data to pass to the template
     * @return string The compiled view
     */
    public static function render(string $template, array $data = []): string
    {
        $templatePath = WPPS_PLUGIN_DIR . 'templates/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            return 'Template not found: ' . $template;
        }
        
        $content = file_get_contents($templatePath);
        $compiledContent = self::compileTemplate($content);
        
        // Extract data to make variables available in template
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Use eval to process the compiled template
        eval('?>' . $compiledContent);
        
        // Get the buffer content and clean the buffer
        return ob_get_clean();
    }
    
    /**
     * Render a view and print it
     *
     * @param string $template The template name
     * @param array  $data     The data to pass to the template
     * @return void
     */
    public static function display(string $template, array $data = []): void
    {
        echo self::render($template, $data);
    }
    
    /**
     * Compile a template with blade-style syntax
     *
     * @param string $content The template content
     * @return string The compiled template
     */
    private static function compileTemplate(string $content): string
    {
        // Process basic blade directives
        $content = self::compileComments($content);
        $content = self::compileEchos($content);
        $content = self::compileStatements($content);
        
        return $content;
    }
    
    /**
     * Compile Blade comments into PHP comments
     *
     * @param string $content The template content
     * @return string The compiled content
     */
    private static function compileComments(string $content): string
    {
        return preg_replace('/\{\{--(.+?)(--\}\})/', '<?php /* $1 */ ?>', $content);
    }
    
    /**
     * Compile Blade echo statements into PHP echo statements
     *
     * @param string $content The template content
     * @return string The compiled content
     */
    private static function compileEchos(string $content): string
    {
        // Compile escaped echoes
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>', $content);
        
        // Compile unescaped echoes
        $content = preg_replace('/\{\!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $content);
        
        return $content;
    }
    
    /**
     * Compile Blade statements into PHP statements
     *
     * @param string $content The template content
     * @return string The compiled content
     */
    private static function compileStatements(string $content): string
    {
        // Compile if statements
        $content = preg_replace('/@if\s*\((.*)\)/', '<?php if ($1): ?>', $content);
        $content = preg_replace('/@elseif\s*\((.*)\)/', '<?php elseif ($1): ?>', $content);
        $content = preg_replace('/@else/', '<?php else: ?>', $content);
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);
        
        // Compile loops
        $content = preg_replace('/@foreach\s*\((.*)\)/', '<?php foreach ($1): ?>', $content);
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);
        
        $content = preg_replace('/@for\s*\((.*)\)/', '<?php for ($1): ?>', $content);
        $content = preg_replace('/@endfor/', '<?php endfor; ?>', $content);
        
        $content = preg_replace('/@while\s*\((.*)\)/', '<?php while ($1): ?>', $content);
        $content = preg_replace('/@endwhile/', '<?php endwhile; ?>', $content);
        
        // Compile includes
        $content = preg_replace('/@include\s*\([\'\"](.+?)[\'\"]\)/', '<?php echo self::render(\'$1\', get_defined_vars()); ?>', $content);
        
        // Compile php opening and closing tags
        $content = preg_replace('/@php/', '<?php', $content);
        $content = preg_replace('/@endphp/', '?>', $content);
        
        // Compile sections (for layout support)
        $content = preg_replace('/@section\s*\([\'\"](.+?)[\'\"]\)/', '<?php $this->startSection(\'$1\'); ?>', $content);
        $content = preg_replace('/@endsection/', '<?php $this->endSection(); ?>', $content);
        $content = preg_replace('/@yield\s*\([\'\"](.+?)[\'\"]\)/', '<?php echo $this->yieldContent(\'$1\'); ?>', $content);
        
        return $content;
    }
    
    /**
     * Get the current timestamp for a template
     *
     * @param string $path The template path
     * @return int The last modified timestamp
     */
    private static function getTemplateTimestamp(string $path): int
    {
        return filemtime($path);
    }
    
    /**
     * Get a template from the cache or compile it
     *
     * @param string $template The template name
     * @return string The compiled template
     */
    private static function getCachedTemplate(string $template): string
    {
        $templatePath = WPPS_PLUGIN_DIR . 'templates/' . $template . '.php';
        $cachePath = WPPS_PLUGIN_DIR . 'cache/' . md5($template) . '.php';
        
        // Create cache directory if it doesn't exist
        if (!file_exists(WPPS_PLUGIN_DIR . 'cache')) {
            mkdir(WPPS_PLUGIN_DIR . 'cache', 0755, true);
        }
        
        // Compile and cache if needed
        if (!file_exists($cachePath) || 
            self::getTemplateTimestamp($templatePath) > self::getTemplateTimestamp($cachePath)) {
            $content = file_get_contents($templatePath);
            $compiled = self::compileTemplate($content);
            file_put_contents($cachePath, $compiled);
        }
        
        return file_get_contents($cachePath);
    }
}