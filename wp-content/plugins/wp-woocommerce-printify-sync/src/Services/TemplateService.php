<?php
/**
 * Template Service
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Class TemplateService
 *
 * Handles template rendering
 */
class TemplateService
{
    /**
     * Template directory
     *
     * @var string
     */
    private string $template_dir;

    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Constructor
     *
     * @param LoggerService $logger Logger service.
     */
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
        $this->template_dir = WPWPS_PLUGIN_DIR . 'templates/';
    }

    /**
     * Render a template
     *
     * @param string $template Template path relative to templates directory
     * @param array  $data     Data to pass to the template
     * @param bool   $echo     Whether to echo the template or return it
     * @return string|null Template content if $echo is false
     */
    public function render(string $template, array $data = [], bool $echo = true): ?string
    {
        // Get the full template path
        $template_path = $this->getTemplatePath($template);

        if (!file_exists($template_path)) {
            $this->logger->error('Template not found', [
                'template' => $template,
                'path' => $template_path,
            ]);
            return null;
        }

        // Start output buffering
        ob_start();

        // Extract data to make it available in the template
        if (!empty($data)) {
            extract($data);
        }

        // Include the template
        include $template_path;

        // Get the buffered content
        $output = ob_get_clean();

        // Process template directives
        $output = $this->processDirectives($output, $data);

        if ($echo) {
            echo $output;
            return null;
        }

        return $output;
    }

    /**
     * Get the template path
     *
     * @param string $template Template path relative to templates directory
     * @return string
     */
    private function getTemplatePath(string $template): string
    {
        // If the template doesn't end with .php, add it
        if (!preg_match('/\.php$/', $template)) {
            $template .= '.php';
        }

        // Prefix with wpwps- if not already prefixed
        if (!preg_match('/^wpwps-/', basename($template)) && !preg_match('/\/wpwps-/', $template)) {
            $template_parts = explode('/', $template);
            $last_part = array_pop($template_parts);
            $template_parts[] = 'wpwps-' . $last_part;
            $template = implode('/', $template_parts);
        }

        // Return the full path
        return $this->template_dir . $template;
    }

    /**
     * Process template directives
     *
     * @param string $content Template content
     * @param array  $data    Template data
     * @return string
     */
    private function processDirectives(string $content, array $data): string
    {
        // Process includes
        $content = preg_replace_callback(
            '/@include\([\'"](.*?)[\'"](,\s*(\[.*?\]))?\)/',
            function ($matches) use ($data) {
                // Get the include template path
                $include_template = trim($matches[1]);
                
                // Get additional data if provided
                $include_data = $data;
                if (isset($matches[3])) {
                    $additional_data = eval('return ' . $matches[3] . ';');
                    if (is_array($additional_data)) {
                        $include_data = array_merge($include_data, $additional_data);
                    }
                }
                
                // Render the include template
                $include_content = $this->render($include_template, $include_data, false);
                return $include_content ?? '';
            },
            $content
        );

        // Process conditionals
        $content = preg_replace_callback(
            '/@if\((.*?)\)(.*?)(?:@else(.*?))?@endif/s',
            function ($matches) use ($data) {
                $condition = trim($matches[1]);
                $if_content = $matches[2];
                $else_content = isset($matches[3]) ? $matches[3] : '';
                
                // Extract variables from data to make them available in the condition
                if (!empty($data)) {
                    extract($data);
                }
                
                // Evaluate the condition
                $result = eval('return ' . $condition . ';');
                
                return $result ? $if_content : $else_content;
            },
            $content
        );

        // Process foreach loops
        $content = preg_replace_callback(
            '/@foreach\((.*?) as (.*?)\)(.*?)@endforeach/s',
            function ($matches) use ($data) {
                $array_expr = trim($matches[1]);
                $iterator = trim($matches[2]);
                $loop_content = $matches[3];
                
                // Extract variables from data
                if (!empty($data)) {
                    extract($data);
                }
                
                // Get the array to iterate
                $array = eval('return ' . $array_expr . ';');
                if (!is_array($array) && !($array instanceof \Traversable)) {
                    return '';
                }
                
                // Process the loop
                $output = '';
                foreach ($array as $key => $value) {
                    // Handle key => value syntax
                    if (strpos($iterator, '=>') !== false) {
                        list($key_var, $value_var) = array_map('trim', explode('=>', $iterator));
                        $$key_var = $key;
                        $$value_var = $value;
                    } else {
                        $$iterator = $value;
                    }
                    
                    // Capture the loop iteration
                    $iteration_content = $loop_content;
                    
                    // Replace variables in loop content
                    ob_start();
                    eval('?>' . $iteration_content . '<?php ');
                    $output .= ob_get_clean();
                }
                
                return $output;
            },
            $content
        );

        // Process partials
        $content = preg_replace_callback(
            '/@partial\([\'"](.*?)[\'"](,\s*(\[.*?\]))?\)/',
            function ($matches) use ($data) {
                $partial_name = trim($matches[1]);
                $partial_data = $data;
                
                if (isset($matches[3])) {
                    $additional_data = eval('return ' . $matches[3] . ';');
                    if (is_array($additional_data)) {
                        $partial_data = array_merge($data, $additional_data);
                    }
                }
                
                return $this->renderPartial($partial_name, $partial_data);
            },
            $content
        );

        // Process blade-style variables 
        $content = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function($matches) use ($data) {
            return $this->evaluateExpression($matches[1], $data);
        }, $content);

        // Process blade-style conditionals
        $content = preg_replace_callback('/@if\((.*?)\)(.*?)(?:@else(.*?))?@endif/s', 
            function($matches) use ($data) {
                return $this->processConditional($matches, $data);
            }, 
            $content
        );

        // Process blade-style loops
        $content = preg_replace_callback('/@foreach\((.*?) as (.*?)\)(.*?)@endforeach/s',
            function($matches) use ($data) {
                return $this->processLoop($matches, $data);
            },
            $content
        );

        return $content;
    }

    /**
     * Render a partial template
     */
    private function renderPartial(string $name, array $data = []): string {
        $partial_path = $this->template_dir . 'partials/' . $name . '.php';
        
        if (!file_exists($partial_path)) {
            $this->logger->error('Partial template not found', [
                'partial' => $name,
                'path' => $partial_path
            ]);
            return '';
        }

        ob_start();
        extract($data);
        include $partial_path;
        return ob_get_clean();
    }

    /**
     * Evaluate an expression with template data
     */
    private function evaluateExpression(string $expression, array $data): string {
        extract($data);
        try {
            return htmlspecialchars(eval("return {$expression};"));
        } catch (\Throwable $e) {
            $this->logger->error('Expression evaluation failed', [
                'expression' => $expression,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Process conditional directive
     */
    private function processConditional(array $matches, array $data): string {
        $condition = trim($matches[1]);
        $if_content = $matches[2];
        $else_content = isset($matches[3]) ? $matches[3] : '';

        extract($data);
        
        try {
            $result = eval("return {$condition};");
            return $result ? $if_content : $else_content;
        } catch (\Throwable $e) {
            $this->logger->error('Conditional evaluation failed', [
                'condition' => $condition,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Process loop directive
     */
    private function processLoop(array $matches, array $data): string {
        $array_expr = trim($matches[1]);
        $iterator = trim($matches[2]);
        $loop_content = $matches[3];

        extract($data);

        try {
            $array = eval("return {$array_expr};");
            if (!is_array($array) && !($array instanceof \Traversable)) {
                return '';
            }

            $output = '';
            foreach ($array as $key => $value) {
                $loop_data = array_merge($data, [
                    'loop' => [
                        'index' => $key + 1,
                        'iteration' => $key,
                        'first' => $key === 0,
                        'last' => $key === count($array) - 1
                    ]
                ]);

                if (strpos($iterator, '=>') !== false) {
                    list($k, $v) = array_map('trim', explode('=>', $iterator));
                    $loop_data[$k] = $key;
                    $loop_data[$v] = $value;
                } else {
                    $loop_data[$iterator] = $value;
                }

                $iteration_content = $this->processDirectives($loop_content, $loop_data);
                $output .= $iteration_content;
            }
            return $output;

        } catch (\Throwable $e) {
            $this->logger->error('Loop processing failed', [
                'array_expr' => $array_expr,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Check if a template exists
     *
     * @param string $template Template path relative to templates directory
     * @return bool
     */
    public function templateExists(string $template): bool
    {
        $template_path = $this->getTemplatePath($template);
        return file_exists($template_path);
    }

    /**
     * Render a partial template
     */
    public function partial(string $name, array $data = []): string 
    {
        $partial_path = $this->template_dir . 'partials/' . $name . '.php';
        
        if (!file_exists($partial_path)) {
            $this->logger->error('Partial template not found', [
                'partial' => $name,
                'path' => $partial_path
            ]);
            return '';
        }

        // Extract data for use in template
        extract($data);

        // Capture output
        ob_start();
        include $partial_path;
        return ob_get_clean();
    }
}
