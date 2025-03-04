<?php

/**
 * EnqueueAssets
 *
 * This class handles the automatic loading of all frontend and admin scripts/styles,
 * ensuring proper enqueueing, versioning, and minification where needed.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utilities
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utilities;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\EnqueueHelper;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Minifier;

class EnqueueAssets {
    /**
     * Version number for assets, used for cache busting.
     *
     * @var string
     */
    private static $asset_version = '1.0.7';

    /**
     * Registers hooks for enqueueing scripts and styles.
     */
    public static function register() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);
    }

    /**
     * Enqueues scripts and styles for the admin panel.
     */
    public static function enqueue_admin_scripts() {
        self::enqueue_external_assets();
        self::enqueue_assets_from_directory('assets/css/admin', 'admin-style', 'style');
        self::enqueue_assets_from_directory('assets/js/admin', 'admin-script', 'script');
    }

    /**
     * Enqueues scripts and styles for the frontend.
     */
    public static function enqueue_scripts() {
        self::enqueue_external_assets();
        self::enqueue_assets_from_directory('assets/css', 'printify-sync-style', 'style');
        self::enqueue_assets_from_directory('assets/js', 'printify-sync-script', 'script');
    }

    /**
     * Enqueues external libraries such as Bootstrap and jQuery.
     */
    private static function enqueue_external_assets() {
        wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', [], self::$asset_version);
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], self::$asset_version);
        wp_enqueue_script('jquery-cdn', 'https://code.jquery.com/jquery-3.6.0.min.js', [], self::$asset_version, true);
        wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery-cdn'], self::$asset_version, true);
    }

    /**
     * Scans and enqueues all assets from the given directory.
     *
     * @param string $directory Directory path relative to the plugin root.
     * @param string $handle_prefix Prefix for asset handles.
     * @param string $type Type of asset ('style' or 'script').
     */
    private static function enqueue_assets_from_directory($directory, $handle_prefix, $type) {
        $dir_path = plugin_dir_path(__FILE__) . $directory;
        $dir_url = plugin_dir_url(__FILE__) . $directory;

        if (!is_dir($dir_path)) {
            return;
        }

        $files = self::scan_directory_recursively($dir_path);
        foreach ($files as $file_path) {
            if (is_file($file_path)) {
                $final_path = Minifier::minify_and_save($file_path) ?: $file_path;
                $file_url = EnqueueHelper::convert_path_to_url($final_path, $dir_path, $dir_url);
                $handle = EnqueueHelper::generate_handle($final_path, $handle_prefix);

                if ($type === 'style' && str_ends_with($final_path, '.css')) {
                    wp_enqueue_style($handle, $file_url, [], self::$asset_version);
                } elseif ($type === 'script' && str_ends_with($final_path, '.js')) {
                    wp_enqueue_script($handle, $file_url, ['jquery'], self::$asset_version, true);
                }
            }
        }
    }

    /**
     * Recursively scans a directory for asset files.
     *
     * @param string $directory Directory to scan.
     * @return array List of file paths.
     */
    private static function scan_directory_recursively($directory) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
