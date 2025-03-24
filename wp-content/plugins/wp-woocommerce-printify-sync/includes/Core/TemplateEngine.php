<?php
/**
 * Blade-like template engine.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Class TemplateEngine
 */
class TemplateEngine {
    /**
     * Template directory
     *
     * @var string
     */
    private $templateDir;
    
    /**
     * Cache directory
     *
     * @var string
     */
    private $cacheDir;
    
    /**
     * Constructor
     *
     * @param string $templateDir Optional template directory.
     * @param string $cacheDir Optional cache directory.
     */
    public function __construct($templateDir = null, $cacheDir = null) {
        $this->templateDir = $templateDir ?? WPWPS_PLUGIN_DIR . 'templates';
        $this->cacheDir = $cacheDir ?? WPWPS_PLUGIN_DIR . 'cache';
        
        // Create cache directory if it doesn't exist
        if (!file_exists($this->cacheDir)) {
            wp_mkdir_p($this->cacheDir);
            
            // Add index.php for security
            file_put_contents($this->cacheDir . '/index.php', '<?php // Silence is golden');
            
            // Add .htaccess for security
            file_put_contents($this->cacheDir . '/.htaccess', 'Deny from all');
        }
    }
    
    /**
     * Render a template with data
     *
     * @param string $template Template name.
     * @param array  $data Data to pass to the template.
     * @param bool   $return Whether to return the output or echo it.
     * @return string|void Output if $return is true, otherwise void.
     */
    public function render($template, $data = [], $return = false) {
        $templatePath = $this->findTemplate($template);
        if (!$templatePath) {
            /* translators: %s: Template name */
            $error = sprintf(__('Template %s not found.', 'wp-woocommerce-printify-sync'), $template);
            
            if (WP_DEBUG) {
                throw new \Exception($error);
            }
            
            return $return ? $error : print($error);
        }
        
        $cachePath = $this->getCachePath($templatePath);
        
        // Compile template if needed
        if (!file_exists($cachePath) || filemtime($templatePath) > filemtime($cachePath)) {
            $this->compileTemplate($templatePath, $cachePath);
        }
        
        // Extract data to make variables accessible in the template
        extract($data);
        
        // Start output buffering
        ob_start();
        include $cachePath;
        $output = ob_get_clean();
        
        if ($return) {
            return $output;
        }
        
        echo $output;
    }
    
    /**
     * Find template file
     *
     * @param string $template Template name.
     * @return string|false Path to template or false if not found.
     */
    private function findTemplate($template) {
        // Ensure template has wpwps- prefix
        if (strpos($template, 'wpwps-') !== 0) {
            $template = 'wpwps-' . $template;
        }
        
        // Add .php extension if not present
        if (!preg_match('/\.php$/', $template)) {
            $template .= '.php';
        }
        
        $path = $this->templateDir . '/' . $template;
        
        return file_exists($path) ? $path : false;
    }
    
    /**
     * Get cache path for a template
     *
     * @param string $templatePath Original template path.
     * @return string Cache path.
     */
    private function getCachePath($templatePath) {
        $relativePath = str_replace($this->templateDir, '', $templatePath);
        $cacheKey = md5($templatePath) . '_' . basename($templatePath);
        
        return $this->cacheDir . '/' . $cacheKey;
    }
    
    /**
     * Compile a template
     *
     * @param string $templatePath Template path.
     * @param string $cachePath Cache path.
     * @return bool Success status.
     */
    private function compileTemplate($templatePath, $cachePath) {
        $content = file_get_contents($templatePath);
        
        // Process template directives
        $content = $this->compileDirectives($content);
        
        return file_put_contents($cachePath, $content);
    }
    
    /**
     * Compile template directives
     *
     * @param string $content Template content.
     * @return string Compiled content.
     */
    private function compileDirectives($content) {
        // Replace {{ }} with <?php echo htmlspecialchars(...) ?>
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>', $content);
        
        // Replace {!! !!} with <?php echo ... ?>
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $content);
        
        // Replace @if, @elseif, @else
        $content = preg_replace('/@if\s*\((.*)\)/', '<?php if ($1): ?>', $content);
        $content = preg_replace('/@elseif\s*\((.*)\)/', '<?php elseif ($1): ?>', $content);
        $content = str_replace('@else', '<?php else: ?>', $content);
        $content = str_replace('@endif', '<?php endif; ?>', $content);
        
        // Replace @foreach
        $content = preg_replace('/@foreach\s*\((.*)\)/', '<?php foreach ($1): ?>', $content);
        $content = str_replace('@endforeach', '<?php endforeach; ?>', $content);
        
        // Replace @for
        $content = preg_replace('/@for\s*\((.*)\)/', '<?php for ($1): ?>', $content);
        $content = str_replace('@endfor', '<?php endfor; ?>', $content);
        
        // Replace @while
        $content = preg_replace('/@while\s*\((.*)\)/', '<?php while ($1): ?>', $content);
        $content = str_replace('@endwhile', '<?php endwhile; ?>', $content);
        
        // Replace @php
        $content = str_replace('@php', '<?php', $content);
        $content = str_replace('@endphp', '?>', $content);
        
        // Replace @include
        $content = preg_replace('/@include\s*\([\'"](.*)[\'"](?:,\s*(.*))?\)/', '<?php $this->render(\'$1\', $2 ?? [], true); ?>', $content);
        
        return $content;
    }
}
