<?php

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

require_once(dirname(__FILE__) . '/wp/wp-load.php');

// Allow WordPress to detect HTTPS when used behind a reverse proxy or a load balancer
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS']  = 'on';
}

// Handle ngrok domain
if (isset($_SERVER['HTTP_X_ORIGINAL_HOST'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_ORIGINAL_HOST'];
}

// Load environment variables only, removing any hardcoded values

define('DB_NAME', getenv('WP_DB_NAME'));
define('DB_USER', getenv('WP_DB_USER'));
define('DB_PASSWORD', getenv('WP_DB_PASSWORD'));
define('DB_HOST', getenv('WP_DB_HOST'));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

define('WP_HOME', getenv('WP_HOME'));
define('WP_SITEURL', getenv('WP_SITEURL'));

define('WP_CONTENT_DIR', getenv('WP_CONTENT_DIR'));
define('WP_CONTENT_URL', getenv('WP_CONTENT_URL'));

define('WP_TABLE_PREFIX', getenv('WP_TABLE_PREFIX'));

define('WP_DEBUG', filter_var(getenv('WP_DEBUG'), FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_LOG', filter_var(getenv('WP_DEBUG_LOG'), FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_DISPLAY', filter_var(getenv('WP_DEBUG_DISPLAY'), FILTER_VALIDATE_BOOLEAN));

define('WP_REDIS_HOST', getenv('WP_REDIS_HOST'));
define('WP_REDIS_PORT', getenv('WP_REDIS_PORT'));
define('WP_REDIS_PASSWORD', getenv('WP_REDIS_PASSWORD'));

define('AUTH_KEY', getenv('AUTH_KEY'));
define('SECURE_AUTH_KEY', getenv('SECURE_AUTH_KEY'));
define('LOGGED_IN_KEY', getenv('LOGGED_IN_KEY'));
define('NONCE_KEY', getenv('NONCE_KEY'));
define('AUTH_SALT', getenv('AUTH_SALT'));
define('SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT'));
define('LOGGED_IN_SALT', getenv('LOGGED_IN_SALT'));
define('NONCE_SALT', getenv('NONCE_SALT'));

define('DISABLE_WP_CRON', filter_var(getenv('DISABLE_WP_CRON'), FILTER_VALIDATE_BOOLEAN));
define('FILESYSTEM_METHOD', getenv('FILESYSTEM_METHOD'));
define('DISALLOW_FILE_EDIT', filter_var(getenv('DISALLOW_FILE_EDIT'), FILTER_VALIDATE_BOOLEAN));
define('DISALLOW_FILE_MODS', filter_var(getenv('DISALLOW_FILE_MODS'), FILTER_VALIDATE_BOOLEAN));

define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_PORT', getenv('SMTP_PORT'));
define('SMTP_USERNAME', getenv('SMTP_USERNAME'));
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));
define('SMTP_PROTOCOL', getenv('SMTP_PROTOCOL'));
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL'));
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME'));
define('SMTP_AUTH', filter_var(getenv('SMTP_AUTH'), FILTER_VALIDATE_BOOLEAN));

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/wp/');
}
require_once(ABSPATH . 'wp-settings.php');


