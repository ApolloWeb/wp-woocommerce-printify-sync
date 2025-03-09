<?php
/**
 * View Manager
 *
 * Handles rendering views using Blade templating engine.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\View
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\View;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

/**
 * ViewManager Class
 */
class ViewManager {
    /**
     * View factory
     *
     * @var Factory
     */
    private $factory;
    
    /**
     * Blade compiler
     *
     * @var BladeCompiler
     */
    private $blade;
    
    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger Logger instance
     */
    public function __construct(LoggerInterface $logger = null) {
        $this->logger = $logger;
        $this->init();
    }
    
    /**
     * Initialize the view manager
     *
     * @return void
     */
    private function init() {
        try {
            // Create container
            $container = new Container();
            
            // Create filesystem
            $filesystem = new Filesystem();
            
            // Create view finder
            $view_paths = [APOLLOWEB_PRINTIFY_TEMPLATES_PATH];
            $finder = new FileViewFinder($filesystem, $view_paths);
            
            // Create dispatcher
            $dispatcher = new Dispatcher($container);
            
            // Create cache directory if it doesn't exist
            $cache_path = APOLLOWEB_PRINTIFY_PATH . 'cache';
            if (!file_exists($cache_path)) {
                mkdir($cache_path, 0755, true);
            }
            
            // Create blade compiler
            $blade = new BladeCompiler($filesystem, $cache_path);
            
            // Register custom directives
            $this->registerCustomDirectives($blade);
            
            // Set up engine resolver
            $resolver = new EngineResolver();
            $resolver->register('blade', function() use ($blade, $filesystem) {
                return new CompilerEngine($blade, $filesystem);
            });
            
            // Create factory
            $this->factory = new Factory($resolver, $finder, $dispatcher);
            $this->blade = $blade;
            
            if ($this->logger) {
                $this->logger->info('Blade templating engine initialized successfully');
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to initialize Blade templating engine: ' . $e->getMessage(), [
                    'exception' => $e,
                ]);
            }
            
            throw $e;
        }
    }
    
    /**
     * Register custom directives for Blade
     *
     * @param BladeCompiler $blade Blade compiler
     * @return void
     */
    private function registerCustomDirectives(BladeCompiler $blade) {
        // WordPress specific directives
        
        // @wphead - Output wp_head()
        $blade->directive('wphead', function() {
            return '<?php wp_head(); ?>';
        });
        
        // @wpfooter - Output wp_footer()
        $blade->directive('wpfooter', function() {
            return '<?php wp_footer(); ?>';
        });
        
        // @wpnonce - Generate nonce field
        $blade->directive('wpnonce', function($action) {
            return "<?php wp_nonce_field({$action}); ?>";
        });
        
        // @wpurl - Generate WordPress URL
        $blade->directive('wpurl', function($path = '') {
            if (!$path) {
                return '<?php echo get_bloginfo("url"); ?>';
            }
            return "<?php echo get_bloginfo('url') . {$path}; ?>";
        });
        
        // @asset - Generate asset URL
        $blade->directive('asset', function($path) {
            return "<?php echo APOLLOWEB_PRINTIFY_URL . 'assets/' . {$path}; ?>";
        });
    }
    
    /**
     * Render a view
     *
     * @param string $view View name
     * @param array  $data View data
     * @param array  $mergeData Additional data to merge
     * @return string Rendered view
     */
    public function render($view, $data = [], $mergeData = []) {
        try {
            return $this->factory->make($view, $data, $mergeData)->render();
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to render view: ' . $e->getMessage(), [
                    'view' => $view,
                    'exception' => $e,
                ]);
            }
            
            return $this->renderFallback($view, $data, $e);
        }
    }
    
    /**
     * Render a fallback view in case of errors
     *
     * @param string     $view View name
     * @param array      $data View data
     * @param \Exception $exception Exception that occurred
     * @return string Fallback HTML
     */
    private function renderFallback($view, $data, \Exception $exception) {
        $html = '<div class="apolloweb-printify-error">';
        $html .= '<h3>Error rendering view: ' . esc_html($view) . '</h3>';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $html .= '<p><strong>Error:</strong> ' . esc_html($exception->getMessage()) . '</p>';
            $html .= '<pre>' . esc_html($exception->getTraceAsString()) . '</pre>';
        } else {
            $html .= '<p>An error occurred while rendering this view. Please check the logs for details.</p>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Check if a view exists
     *
     * @param string $view View name
     * @return bool
     */
    public function exists($view) {
        return $this->factory->exists($view);
    }
    
    /**
     * Share data across all views
     *
     * @param string|array $key Key or array of key/value pairs
     * @param mixed        $value Value to share
     * @return void
     */
    public function share($key, $value = null) {
        $this->factory->share($key, $value);
    }
    
    /**
     * Get the underlying Blade compiler
     *
     * @return BladeCompiler
     */
    public function compiler() {
        return $this->blade;
    }
}