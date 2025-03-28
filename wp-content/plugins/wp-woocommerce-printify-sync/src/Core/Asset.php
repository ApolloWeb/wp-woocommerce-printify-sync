<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Asset
{
    /**
     * Enqueue a CSS file.
     *
     * @param string $handle The handle for the stylesheet.
     * @param string $src The source URL of the stylesheet.
     * @param array $deps An array of dependencies.
     * @param string|null $ver The version of the stylesheet.
     * @param string $media The media for which this stylesheet has been defined.
     */
    public static function enqueueStyle($handle, $src, $deps = [], $ver = null, $media = 'all')
    {
        wp_enqueue_style($handle, $src, $deps, $ver, $media);
    }

    /**
     * Enqueue a JavaScript file.
     *
     * @param string $handle The handle for the script.
     * @param string $src The source URL of the script.
     * @param array $deps An array of dependencies.
     * @param string|null $ver The version of the script.
     * @param bool $inFooter Whether to enqueue the script in the footer.
     */
    public static function enqueueScript($handle, $src, $deps = [], $ver = null, $inFooter = false)
    {
        wp_enqueue_script($handle, $src, $deps, $ver, $inFooter);
    }

    /**
     * Register a CSS file without enqueuing it.
     *
     * @param string $handle The handle for the stylesheet.
     * @param string $src The source URL of the stylesheet.
     * @param array $deps An array of dependencies.
     * @param string|null $ver The version of the stylesheet.
     * @param string $media The media for which this stylesheet has been defined.
     */
    public static function registerStyle($handle, $src, $deps = [], $ver = null, $media = 'all')
    {
        wp_register_style($handle, $src, $deps, $ver, $media);
    }

    /**
     * Register a JavaScript file without enqueuing it.
     *
     * @param string $handle The handle for the script.
     * @param string $src The source URL of the script.
     * @param array $deps An array of dependencies.
     * @param string|null $ver The version of the script.
     * @param bool $inFooter Whether to enqueue the script in the footer.
     */
    public static function registerScript($handle, $src, $deps = [], $ver = null, $inFooter = false)
    {
        wp_register_script($handle, $src, $deps, $ver, $inFooter);
    }

    /**
     * Enqueue core CSS and JavaScript files including Bootstrap, Font Awesome, and Chart.js.
     */
    public static function enqueueCoreAssets()
    {
        // Enqueue core CSS files
        self::enqueueStyle('wpwps-fontawesome', WPWPS_ASSETS_URL . 'core/css/fontawesome.min.css', [], WPWPS_VERSION);
        self::enqueueStyle('wpwps-bootstrap', WPWPS_ASSETS_URL . 'core/css/bootstrap.min.css', [], WPWPS_VERSION);

        // Enqueue core JavaScript files
        self::enqueueScript('wpwps-bootstrap', WPWPS_ASSETS_URL . 'core/js/bootstrap.bundle.min.js', ['jquery'], WPWPS_VERSION, true);
        self::enqueueScript('wpwps-chart', WPWPS_ASSETS_URL . 'core/js/chart.min.js', [], WPWPS_VERSION, true);
    }

    /**
     * Enqueue all necessary assets for the plugin.
     */
    public static function enqueueAssets()
    {
        // Core assets
        self::enqueueCoreAssets();

        // Get current admin page
        $page = isset($_GET['page']) ? $_GET['page'] : '';

        // Enqueue page-specific CSS files
        $css_files = [
            'wpwps-dashboard' => 'dashboard',
            'wpwps-orders' => 'orders',
            'wpwps-products' => 'products',
            'wpwps-settings' => 'settings',
            'wpwps-shipping' => 'shipping',
            'wpwps-tickets' => 'tickets',
            'wpwps-logs' => 'logs'
        ];

        if (isset($css_files[$page])) {
            $file = $css_files[$page];
            self::enqueueStyle(
                "wpwps-{$file}", 
                WPWPS_ASSETS_URL . "css/wpwps-{$file}.css",
                ['wpwps-bootstrap', 'wpwps-fontawesome'],
                WPWPS_VERSION
            );
        }

        // Enqueue page-specific JS files
        $js_files = [
            'wpwps-dashboard' => 'dashboard',
            'wpwps-orders' => 'orders', 
            'wpwps-products' => 'products',
            'wpwps-settings' => 'settings',
            'wpwps-shipping' => 'shipping',
            'wpwps-tickets' => 'tickets'
        ];

        if (isset($js_files[$page])) {
            $file = $js_files[$page];
            self::enqueueScript(
                "wpwps-{$file}-js",
                WPWPS_ASSETS_URL . "js/wpwps-{$file}.js",
                ['jquery', 'wpwps-bootstrap'],
                WPWPS_VERSION,
                true
            );
        }
    }
}