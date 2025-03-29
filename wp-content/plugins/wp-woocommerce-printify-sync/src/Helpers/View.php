<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

// Manually include the BladeOne class
require_once plugin_dir_path(dirname(__DIR__)) . 'lib/BladeOne/BladeOne.php';

use eftec\bladeone\BladeOne;

class View
{
    private static $engine = null;

    public static function render($view, $data = [])
    {
        self::initEngine();
        return self::$engine->run($view, $data);
    }

    public static function initEngine()
    {
        if (self::$engine === null) {
            $views = WPWPS_TEMPLATES_PATH;
            $cache = WPWPS_CACHE_PATH;

            // Ensure BladeOne is properly initialized
            self::$engine = new BladeOne($views, $cache, BladeOne::MODE_AUTO);
        }
    }
}