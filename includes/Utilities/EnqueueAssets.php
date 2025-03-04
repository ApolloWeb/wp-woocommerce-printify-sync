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

class EnqueueAssets {
    /**
     * Version number for assets, used for cache busting.
     *
     * @var string
     */
    private static $asset_version;
    
    /**
     * Plugin root directory path
     *
     * @var string
     */
    private static $plugin_dir;
    
    /**
     * Plugin root URL
     *
     * @var string
     */
    private static $plugin_url;

    /**
     * Initialize properties and register hooks
     */
    public static function init() {
        // Use defined constants if available
        if (defined('PRINTIFY_SYNC_VERSION')) {
            self::$asset_version = PRINTIFY_SYNC_VERSION;
        } else {
            self::$asset_version = '1.0.7';
        }
        
        // Use plugin constants if defined
        if (defined('PRINTIFY_SYNC_PATH') && defined('PRINTIFY_SYNC_URL')) {
            self::$plugin_dir = PRINTIFY_SYNC_PATH;
            self::$plugin_url = PRINTIFY_SYNC_URL;
        } else {
            // Fallback calculation
            self::$plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
            self::$plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
        }
        
        // Debug paths
        if (function_exists('printify_sync_debug')) {
            printify_sync_debug('EnqueueAssets paths:');
            printify_sync_debug('- Plugin dir: ' . self::$plugin_dir);
            printify_sync_debug('- Plugin URL: ' . self::$plugin_url);
        }
        
        self::register();
    }

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
        if (!isset(self::$plugin_dir) || !isset(self::$plugin_url)) {
            self::init();
        }
        
        self::enqueue_external_assets();
        self::enqueue_assets_from_directory('assets/css/admin', 'admin-style', 'style');
        self::enqueue_assets_from_directory('assets/js/admin', 'admin-script', 'script');
    }

    /**
     * Enqueues scripts and styles for the frontend.
     */
    public static function enqueue_scripts() {
        if (!isset(self::$plugin_dir) || !isset(self::$plugin_url)) {
            self::init();
        }
        
        self::enqueue_external_assets();
        self::enqueue_assets_from_directory('assets/css', 'printify-sync-style', 'style', ['admin']);
        self::enqueue_assets_from_directory('assets/js', 'printify-sync-script', 'script', ['admin']);
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
     * @param array $excluded_dirs Directories to exclude from scanning
     */
    private static function enqueue_assets_from_directory($directory, $handle_prefix, $type, $excluded_dirs = []) {
        $dir_path = self::$plugin_dir . $directory;
        $dir_url = self::$plugin_url . $directory;

        if (!is_dir($dir_path)) {
            if (function_exists('printify_sync_debug')) {
                printify_sync_debug("Directory not found: $dir_path");
            }
            return;
        }

        $files = self::scan_directory_recursively($dir_path, $excluded_dirs);
        if (function_exists('printify_sync_debug')) {
            printify_sync_debug("Found " . count($files) . " files in $dir_path");
        }
        
        foreach ($files as $file_path) {
            if (is_file($file_path)) {
                // Check file extension
                $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
                if (($type === 'style' && $file_ext !== 'css') || 
                    ($type === 'script' && $file_ext !== 'js')) {
                    continue;
                }
                
                // Check for minified version
                $minified_path = EnqueueHelper::get_minified_file($file_path);
                $final_path = $minified_path ? $minified_path : $file_path;
                
                // Get URL from path
                $file_url = str_replace(self::$plugin_dir, self::$plugin_url, $final_path);
                
                // Generate handle
                $handle = EnqueueHelper::generate_handle($final_path, $handle_prefix);

                // Debug the handle generation
                if (function_exists('printify_sync_debug')) {
                    printify_sync_debug("Enqueuing: $handle → " . basename($final_path));
                }

                // Enqueue the asset
                if ($type === 'style') {
                    wp_enqueue_style($handle, $file_url, [], self::$asset_version);
                } elseif ($type === 'script') {
                    wp_enqueue_script($handle, $file_url, ['jquery'], self::$asset_version, true);
                }
            }
        }
    }

    /**
     * Recursively scans a directory for asset files.
     *
     * @param string $directory Directory to scan.
     * @param array $excluded_dirs Directories to exclude
     * @return array List of file paths.
     */
    private static function scan_directory_recursively($directory, $excluded_dirs = []) {
        $files = [];
        
        if (!is_dir($directory)) {
            return $files;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $path = $file->getPathname();
                    
                    // Skip files in excluded directories
                    $skip = false;
                    foreach ($excluded_dirs as $excluded) {
                        if (strpos($path, "/{$excluded}/") !== false) {
                            $skip = true;
                            break;
                        }
                    }
                    
                    if (!$skip) {
                        $files[] = $path;
                    }
                }
            }
        } catch (\Exception $e) {
            if (function_exists('printify_sync_debug')) {
                printify_sync_debug('Error scanning directory ' . $directory . ': ' . $e->getMessage());
            }
        }

        return $files;
    }
}