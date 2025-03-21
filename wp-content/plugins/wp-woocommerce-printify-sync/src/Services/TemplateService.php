<?php
/**
 * Template Service for rendering templates.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Template service for rendering templates.
 */
class TemplateService
{
    /**
     * Templates cache.
     *
     * @var array
     */
    private $cache = [];
    
    /**
     * Render a template with data.
     *
     * @param string $template Template name (without extension).
     * @param array  $data     Data to pass to the template.
     * @param bool   $echo     Whether to echo the template output or return it.
     * @return string|void The template output if $echo is false.
     */
    public function render($template, $data = [], $echo = true)
    {
        $template_path = $this->findTemplate($template);
        
        if (!$template_path) {
            return '';
        }
        
        // Extract data to make variables available in the template
        extract($data, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        
        // Include the template file
        include $template_path;
        
        // Get the buffered content
        $output = ob_get_clean();
        
        // Process blade-like directives
        $output = $this->processDirectives($output);
        
        if ($echo) {
            echo $output;
            return;
        }
        
        return $output;
    }
    
    /**
     * Find a template file.
     *
     * @param string $template Template name (without extension).
     * @return string|false The template path or false if not found.
     */
    private function findTemplate($template)
    {
        // Check if the template is in the cache
        if (isset($this->cache[$template])) {
            return $this->cache[$template];
        }
        
        // Try to find the template
        $template_file = $template . '.php';
        $template_path = WPWPS_PLUGIN_DIR . 'templates/' . $template_file;
        
        if (file_exists($template_path)) {
            $this->cache[$template] = $template_path;
            return $template_path;
        }
        
        return false;
    }
    
    /**
     * Process blade-like directives in the template.
     *
     * @param string $content Template content.
     * @return string Processed content.
     */
    private function processDirectives($content)
    {
        // Process @if directive
        $content = preg_replace('/@if\s*\((.*?)\)/', '<?php if ($1): ?>', $content);
        
        // Process @elseif directive
        $content = preg_replace('/@elseif\s*\((.*?)\)/', '<?php elseif ($1): ?>', $content);
        
        // Process @else directive
        $content = preg_replace('/@else/', '<?php else: ?>', $content);
        
        // Process @endif directive
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);
        
        // Process @foreach directive
        $content = preg_replace('/@foreach\s*\((.*?)\)/', '<?php foreach ($1): ?>', $content);
        
        // Process @endforeach directive
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);
        
        // Process @for directive
        $content = preg_replace('/@for\s*\((.*?)\)/', '<?php for ($1): ?>', $content);
        
        // Process @endfor directive
        $content = preg_replace('/@endfor/', '<?php endfor; ?>', $content);
        
        // Process @while directive
        $content = preg_replace('/@while\s*\((.*?)\)/', '<?php while ($1): ?>', $content);
        
        // Process @endwhile directive
        $content = preg_replace('/@endwhile/', '<?php endwhile; ?>', $content);
        
        // Process @include directive
        $content = preg_replace_callback('/@include\s*\(\'(.*?)\'(?:,\s*(.*?))?\)/', function($matches) {
            $template = $matches[1];
            $data = isset($matches[2]) ? $matches[2] : '[]';
            return '<?php $this->render(\'' . $template . '\', ' . $data . '); ?>';
        }, $content);
        
        // Process {{ }} expressions
        $content = preg_replace('/\{\{\s*(.*?)\s*\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>', $content);
        
        // Process {!! !!} expressions (unescaped)
        $content = preg_replace('/\{!!\s*(.*?)\s*!!\}/', '<?php echo $1; ?>', $content);
        
        return $content;
    }
    
    /**
     * Render a partial template.
     *
     * @param string $partial Partial template name (without extension).
     * @param array  $data    Data to pass to the template.
     * @return string The partial template output.
     */
    public function partial($partial, $data = [])
    {
        return $this->render('partials/' . $partial, $data, false);
    }
}
