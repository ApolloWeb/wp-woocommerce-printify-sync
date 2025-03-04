<?php/**
 * Minifier * This class provides basic minification for CSS and JS files before enqueueing. * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;class Minifier {
    /**
     * Minifies CSS content.     * @param string $css CSS content to minify.
     * @return string Minified CSS.
     */
    public static function minify_css($css) {
        return preg_replace('/\s+/', ' ', $css);
    }    /**
     * Minifies JS content.     * @param string $js JS content to minify.
     * @return string Minified JS.
     */
    public static function minify_js($js) {
        return preg_replace('/\s+/', ' ', $js);
    }    /**
     * Reads a file and minifies its content.     * @param string $file_path Path to the file.
     * @return string|false Minified content or false on failure.
     */
    public static function minify_file($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }        $content = file_get_contents($file_path);
        $ext = pathinfo($file_path, PATHINFO_EXTENSION);        if ($ext === 'css') {
            return self::minify_css($content);
        } elseif ($ext === 'js') {
            return self::minify_js($content);
        }        return false;
    }    /**
     * Minifies a file and saves the minified version.     * @param string $file_path Path to the original file.
     * @return string|false Path to the minified file or false on failure.
     */
    public static function minify_and_save($file_path) {
        $minified_content = self::minify_file($file_path);
        if ($minified_content !== false) {
            $minified_path = str_replace(['.css', '.js'], ['.min.css', '.min.js'], $file_path);
            file_put_contents($minified_path, $minified_content);
            return $minified_path;
        }
        return false;
    }
} Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: } Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------
#
#
# Commit Hash 16c804f
#
