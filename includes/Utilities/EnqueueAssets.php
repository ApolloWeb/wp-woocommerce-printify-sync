<?php
/**
 * Asset Enqueuing Utility
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utilities
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utilities;

/**
 * Class EnqueueAssets
 * Handles all asset enqueuing for the plugin
 */
class EnqueueAssets {
    /**
     * Plugin version for asset versioning
     *
     * @var string
     */
    private static $version = '1.0.7';

    /**
     * Register the class hooks
     *
     * @return void
     */
    public static function register() {
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontendAssets']);
        add_action('admin_head', [self::class, 'outputCriticalStyles']);
        add_action('admin_footer', [self::class, 'outputGlobalData']);
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook The current admin page
     * @return void
     */
    public static function enqueueAdminAssets($hook) {
        if (strpos($hook, 'printify-sync') === false) {
            return;
        }

        // Base directory & URL for assets
        $plugin_dir = plugin_dir_path(__FILE__);  // Gets absolute path
        $plugin_url = plugin_dir_url(__FILE__);   // Gets plugin URL

        $css_dirs = [
            'assets/css/',
            'assets/admin/css/'
        ];
        
        $js_dirs = [
            'assets/js/',
            'assets/admin/js/'
        ];

        // 🔹 1️⃣ Enqueue External Dependencies First
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
        wp_enqueue_style('google-fonts-poppins', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', [], null);

        wp_enqueue_script('jquery'); // Ensure jQuery is enqueued
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js', ['jquery'], '3.7.0', true);
        wp_enqueue_script('progressbar-js', 'https://cdn.jsdelivr.net/npm/progressbar.js@1.1.0/dist/progressbar.min.js', ['jquery'], '1.1.0', true);

        // 🔹 2️⃣ Enqueue All CSS Files (Regular + Admin)
        foreach ($css_dirs as $dir) {
            foreach (glob($plugin_dir . $dir . "*.css") as $file) {
                $filename = basename($file);
                $handle = 'printify-sync-' . str_replace('.css', '', $filename);
                wp_enqueue_style($handle, $plugin_url . $dir . $filename, [], self::$version);
            }
        }

        // 🔹 3️⃣ Enqueue All JS Files (Regular + Admin)
        foreach ($js_dirs as $dir) {
            foreach (glob($plugin_dir . $dir . "*.js") as $file) {
                $filename = basename($file);
                $handle = 'printify-sync-' . str_replace('.js', '', $filename);
                wp_enqueue_script($handle, $plugin_url . $dir . $filename, ['jquery', 'chart-js', 'progressbar-js'], self::$version, true);
            }
        }

        // Debugging output
        error_log("Admin Assets Enqueued for: " . $hook);
    }

    /**
     * Enqueue frontend scripts and styles
     *
     * @return void
     */
    public static function enqueueFrontendAssets() {
        // Define base paths
        $plugin_dir = plugin_dir_path(__FILE__);
        $plugin_url = plugin_dir_url(__FILE__);

        $css_dir = $plugin_dir . 'assets/css/';
        $js_dir = $plugin_dir . 'assets/js/';

        // Enqueue all frontend CSS
        foreach (glob($css_dir . "*.css") as $file) {
            $filename = basename($file);
            $handle = 'printify-sync-' . str_replace('.css', '', $filename);
            wp_enqueue_style($handle, $plugin_url . 'assets/css/' . $filename, [], self::$version);
        }

        // Enqueue all frontend JS
        foreach (glob($js_dir . "*.js") as $file) {
            $filename = basename($file);
            $handle = 'printify-sync-' . str_replace('.js', '', $filename);
            wp_enqueue_script($handle, $plugin_url . 'assets/js/' . $filename, ['jquery'], self::$version, true);
        }

        error_log("Frontend Assets Enqueued");
    }

    /**
     * Output critical styles for the admin dashboard
     *
     * @return void
     */
    public static function outputCriticalStyles() {
        if (!is_admin()) return;

        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'printify-sync') === false) return;

        echo '<style id="printify-sync-critical-css">
            .dashboard.no-sidebar { display: block; }
            .main-content.full-width { width: 100%; }
            .header-left { display: flex; align-items: center; flex: 1; }
            .logo-container {
                padding: 0 20px; height: 70px; display: flex; align-items: center;
                background: linear-gradient(90deg, #674399 0%, #7f54b3 100%);
            }
            .site-logo { color: white; font-weight: 600; font-size: 20px; }
            .main-nav { height: 70px; flex: 1; }
            .main-nav ul { display: flex; list-style: none; margin: 0; padding: 0; height: 100%; }
            .main-nav a { padding: 0 15px; text-decoration: none; color: #2D3748; font-weight: 500; }
        </style>';
    }

    /**
     * Output global JavaScript data for the admin panel
     *
     * @return void
     */
    public static function outputGlobalData() {
        if (!is_admin()) return;

        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'printify-sync') === false) return;

        echo '<script>
            var printifySyncData = {
                currentDateTime: "' . date("Y-m-d H:i:s") . '",
                currentUser: "' . esc_js(wp_get_current_user()->display_name) . '",
                debug: true
            };
            console.log("Global Data Loaded", printifySyncData);
        </script>';
    }
}
