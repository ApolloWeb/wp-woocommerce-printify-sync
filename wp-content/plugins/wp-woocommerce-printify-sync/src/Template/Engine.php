<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Template;

class Engine
{
    private string $viewsPath;
    private string $cachePath;
    private array $data = [];
    private array $sections = [];
    private string $currentSection = '';

    public function __construct(string $viewsPath, string $cachePath)
    {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
    }

    public function render(string $view, array $data = []): string
    {
        $this->data = array_merge($this->data, $data);
        $cachedFile = $this->getCachedFile($view);

        if (!$this->isCached($view, $cachedFile)) {
            $this->compile($view);
        }

        return $this->evaluateTemplate($cachedFile, $this->data);
    }

    public function directive(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
    }

    private function compile(string $view): void
    {
        $content = file_get_contents($this->getViewPath($view));
        
        // Compile the content
        $content = $this->compileExtends($content);
        $content = $this->compileIncludes($content);
        $content = $this->compileSections($content);
        $content = $this->compileYields($content);
        $content = $this->compileEchos($content);
        $content = $this->compilePhp($content);
        
        // Create cache directory if it doesn't exist
        if (!is_dir(dirname($this->getCachedFile($view)))) {
            mkdir(dirname($this->getCachedFile($view)), 0755, true);
        }

        file_put_contents($this->getCachedFile($view), $content);
    }

    private function compileExtends(string $content): string
    {
        return preg_replace_callback('/@extends\(\'([^\']+)\'\)/', function($matches) {
            return "<?php echo \$this->render('{$matches[1]}'); ?>";
        }, $content);
    }

    private function compileIncludes(string $content): string
    {
        return preg_replace_callback('/@include\(\'([^\']+)\'\)/', function($matches) {
            return "<?php echo \$this->render('{$matches[1]}'); ?>";
        }, $content);
    }

    private function compileSections(string $content): string
    {
        $content = preg_replace_callback('/@section\(\'([^\']+)\'\)(.*?)@endsection/s', function($matches) {
            return "<?php \$this->startSection('{$matches[1]}'); ?>{$matches[2]}<?php \$this->endSection(); ?>";
        }, $content);

        return $content;
    }

    private function compileYields(string $content): string
    {
        return preg_replace_callback('/@yield\(\'([^\']+)\'\)/', function($matches) {
            return "<?php echo \$this->yieldContent('{$matches[1]}'); ?>";
        }, $content);
    }

    private function compileEchos(string $content): string
    {
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars($1); ?>', $content);
        return preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $content);
    }

    private function compilePhp(string $content): string
    {
        return preg_replace_callback('/@php(.*?)@endphp/s', function($matches) {
            return "<?php {$matches[1]} ?>";
        }, $content);
    }

    private function evaluateTemplate(string $file, array $data): string
    {
        extract($data);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    private function getViewPath(string $view): string
    {
        return $this->viewsPath . '/' . str_replace('.', '/', $view) . '.php';
    }

    private function getCachedFile(string $view): string
    {
        return $this->cachePath . '/' . md5($view) . '.php';
    }

    private function isCached(string $view, string $cachedFile): bool
    {
        if (!file_exists($cachedFile)) {
            return false;
        }

        return filemtime($this->getViewPath($view)) <= filemtime($cachedFile);
    }
}