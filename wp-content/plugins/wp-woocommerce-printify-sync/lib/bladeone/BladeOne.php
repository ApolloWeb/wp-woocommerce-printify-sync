<?php
/**
 * Minimal BladeOne implementation
 */

namespace eftec\bladeone;

class BladeOne {
    const MODE_AUTO = 0;      // Automatically determines when to recompile
    const MODE_DEBUG = 1;     // Always recompile
    const MODE_FAST = 2;      // Never recompile
    const MODE_SLOW = 3;      // Use eval() instead of file-based compilation

    private $templatePath;
    private $compiledPath;
    private $variables = [];
    private $mode;

    public function __construct(string $templatePath, string $compiledPath, int $mode = self::MODE_AUTO) {
        $this->templatePath = $templatePath;
        $this->compiledPath = $compiledPath;
        $this->mode = $mode;
    }

    public function run(string $view, array $variables = []): string {
        $this->variables = $variables;
        $template = $this->loadTemplate($view);
        $compiledFile = $this->getCompiledPath($view);

        // Check if we need to recompile based on mode
        $shouldCompile = $this->shouldCompile($view, $compiledFile);

        if ($shouldCompile) {
            $compiled = $this->compileBlade($template);
            if ($this->mode !== self::MODE_SLOW) {
                file_put_contents($compiledFile, $compiled);
            }
        } else {
            $compiled = file_get_contents($compiledFile);
        }

        // Extract variables
        extract($this->variables);

        // Capture output
        ob_start();
        if ($this->mode === self::MODE_SLOW) {
            eval('?>' . $compiled);
        } else {
            include $compiledFile;
        }
        return ob_get_clean();
    }

    private function loadTemplate(string $view): string {
        $file = $this->templatePath . '/' . $view . '.blade.php';
        if (!file_exists($file)) {
            throw new \RuntimeException("View file not found: $file");
        }
        return file_get_contents($file);
    }

    private function getCompiledPath(string $view): string {
        return $this->compiledPath . '/' . md5($view) . '.php';
    }

    private function shouldCompile(string $view, string $compiledFile): bool {
        // Always compile in debug mode
        if ($this->mode === self::MODE_DEBUG) {
            return true;
        }

        // Never compile in fast mode
        if ($this->mode === self::MODE_FAST && file_exists($compiledFile)) {
            return false;
        }

        // In auto mode, compile if file doesn't exist or template is newer
        if ($this->mode === self::MODE_AUTO) {
            if (!file_exists($compiledFile)) {
                return true;
            }
            $templateFile = $this->templatePath . '/' . $view . '.blade.php';
            return filemtime($templateFile) > filemtime($compiledFile);
        }

        // In slow mode, always compile (uses eval)
        return true;
    }

    private function compileBlade(string $template): string {
        // Basic compilation of Blade syntax
        $compiled = preg_replace('/\{\{(.+?)\}\}/', '<?php echo htmlspecialchars($1); ?>', $template);
        $compiled = preg_replace('/@if\((.*?)\)/', '<?php if($1): ?>', $compiled);
        $compiled = preg_replace('/@endif/', '<?php endif; ?>', $compiled);
        $compiled = preg_replace('/@foreach\((.*?)\)/', '<?php foreach($1): ?>', $compiled);
        $compiled = preg_replace('/@endforeach/', '<?php endforeach; ?>', $compiled);

        return $compiled;
    }

    /**
     * Add a custom directive
     */
    public function directive(string $name, callable $handler): void {
        // Implementation would go here
        // For now, just a stub since we're not using custom directives yet
    }
}