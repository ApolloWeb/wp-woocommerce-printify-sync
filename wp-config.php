<?php

// Load Composer dependencies (including dotenv)
require_once __DIR__ . '/vendor/autoload.php';

// Load .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// **Database Configuration (from .env)**
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');

// **Custom WordPress Directories (for John Bloch Setup)**
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/wp/');
}
define('WP_SITEURL', getenv('WP_SITEURL') ?: 'https://' . $_SERVER['HTTP_HOST'] . '/wp');
define('WP_HOME', getenv('WP_HOME') ?: 'https://' . $_SERVER['HTTP_HOST']);

// **Debugging (from .env)**
define('WP_DEBUG', getenv('WP_DEBUG') === 'true');
define('WP_DEBUG_LOG', getenv('WP_DEBUG_LOG') === 'true');
define('WP_DEBUG_DISPLAY', getenv('WP_DEBUG_DISPLAY') === 'true');

// **Set Plugin and Theme Paths (if using custom paths)**
define('WP_PLUGIN_DIR', __DIR__ . '/plugins');
define('WP_PLUGIN_URL', WP_HOME . '/plugins');

define('WP_THEME_DIR', __DIR__ . '/themes');
define('WP_THEME_URL', WP_HOME . '/themes');

// **Include WordPress**
require_once ABSPATH . 'wp-settings.php';
