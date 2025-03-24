<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class View
{
    private static $cache_path;
    private static $template_path;

    public static function init(): void
    {
        self::$cache_path = WPWPS_PLUGIN_DIR . 'cache';
        self::$template_path = WPWPS_PLUGIN_DIR . 'templates';
        
        if (!file_exists(self::$cache_path)) {
            mkdir(self::$cache_path, 0755, true);
        }
    }

    public static function render(string $template, array $data = []): void
    {
        $blade_file = self::$template_path . '/' . $template . '.blade.php';
        $cache_file = self::$cache_path . '/' . md5($template) . '.php';

        if (!file_exists($cache_file) || filemtime($blade_file) > filemtime($cache_file)) {
            $content = file_get_contents($blade_file);
            $content = self::compileBlade($content);
            file_put_contents($cache_file, $content);
        }

        extract($data);
        require $cache_file;
    }

    private static function compileBlade(string $content): string
    {
        // Basic blade directives
        $content = preg_replace('/@if\((.*?)\)/', '<?php if($1): ?>', $content);
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);
        $content = preg_replace('/@foreach\((.*?)\)/', '<?php foreach($1): ?>', $content);
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);
        $content = preg_replace('/\{\{(.*?)\}\}/', '<?php echo htmlspecialchars($1); ?>', $content);
        $content = preg_replace('/\{!!(.*?)!!\}/', '<?php echo $1; ?>', $content);
        
        return $content;
    }
}
