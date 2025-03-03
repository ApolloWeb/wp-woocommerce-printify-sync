<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class EnqueueAssets
{
    public static function register()
    {
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueueAdminAssets']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueueFrontendAssets']);
    }

    public static function enqueueAdminAssets()
    {
        self::enqueueAssets('admin');
    }

    public static function enqueueFrontendAssets()
    {
        self::enqueueAssets('frontend');
    }

    private static function enqueueAssets($type)
    {
        $jsDir = plugin_dir_path(__DIR__) . "assets/js/$type/";
        $cssDir = plugin_dir_path(__DIR__) . "assets/css/$type/";

        $jsFiles = self::getFilesFromDirectory($jsDir, 'js');
        $cssFiles = self::getFilesFromDirectory($cssDir, 'css');

        foreach ($jsFiles as $file) {
            wp_enqueue_script(
                sanitize_title($file),
                plugins_url("assets/js/$type/$file", __DIR__),
                [],
                filemtime($jsDir . $file),
                true
            );
        }

        foreach ($cssFiles as $file) {
            wp_enqueue_style(
                sanitize_title($file),
                plugins_url("assets/css/$type/$file", __DIR__),
                [],
                filemtime($cssDir . $file)
            );
        }
    }

    private static function getFilesFromDirectory($dir, $extension)
    {
        $files = [];
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === $extension) {
                        $files[] = $file;
                    }
                }
                closedir($dh);
            }
        }
        return $files;
    }
}