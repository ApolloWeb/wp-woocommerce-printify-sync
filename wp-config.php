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

// Load environment variables

/** MySQL settings */
define('DB_NAME', getenv('WP_DB_NAME'));
define('DB_USER', getenv('WP_DB_USER'));
define('DB_PASSWORD', getenv('WP_DB_PASSWORD'));
define('DB_HOST', getenv('WP_DB_HOST'));
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

/** Authentication Unique Keys and Salts. */
define('AUTH_KEY', getenv('AUTH_KEY'));
define('SECURE_AUTH_KEY', getenv('SECURE_AUTH_KEY'));
define('LOGGED_IN_KEY', getenv('LOGGED_IN_KEY'));
define('NONCE_KEY', getenv('NONCE_KEY'));
define('AUTH_SALT', getenv('AUTH_SALT'));
define('SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT'));
define('LOGGED_IN_SALT', getenv('LOGGED_IN_SALT'));
define('NONCE_SALT', getenv('NONCE_SALT'));

/** WordPress Database Table prefix. */
$table_prefix = getenv('WP_TABLE_PREFIX');

/** For developers: WordPress debugging mode. */
define('WP_DEBUG', getenv('WP_DEBUG') === 'true');
define('WP_DEBUG_LOG', getenv('WP_DEBUG_LOG') === 'true');
define('WP_DEBUG_DISPLAY', getenv('WP_DEBUG_DISPLAY') === 'true');
define('SCRIPT_DEBUG', getenv('SCRIPT_DEBUG') === 'true');

// Locale Setting
define('WPLANG', getenv('WORDPRESS_LOCALE'));

// File System Permissions
define('FS_METHOD', getenv('FILESYSTEM_METHOD') ?: 'direct');
define('DISALLOW_FILE_EDIT', getenv('DISALLOW_FILE_EDIT') === 'true');
define('DISALLOW_FILE_MODS', getenv('DISALLOW_FILE_MODS') === 'true');

// Memory Limits
define('WP_MEMORY_LIMIT', getenv('WP_MEMORY_LIMIT'));
define('WP_MAX_MEMORY_LIMIT', getenv('WP_MAX_MEMORY_LIMIT'));

// Plugin Management
define('WORDPRESS_PLUGINS', getenv('WORDPRESS_PLUGINS'));
define('WORDPRESS_SKIP_PLUGINS', getenv('WORDPRESS_SKIP_PLUGINS'));

// SMTP Mailer Configuration (Outgoing Emails)
define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_PORT', getenv('SMTP_PORT'));
define('SMTP_USER', getenv('SMTP_USERNAME'));
define('SMTP_PASS', getenv('SMTP_PASSWORD'));
define('SMTP_SECURE', getenv('SMTP_PROTOCOL'));
define('SMTP_AUTH', getenv('SMTP_AUTH') === 'true');
define('SMTP_FROM', getenv('SMTP_FROM_EMAIL'));
define('SMTP_NAME', getenv('SMTP_FROM_NAME'));

// Postie (Incoming Mail via POP3)
define('POSTIE_MAIL_SERVER', getenv('POP3_HOST'));
define('POSTIE_MAIL_PORT', getenv('POP3_PORT'));
define('POSTIE_MAIL_USER', getenv('POP3_USERNAME'));
define('POSTIE_MAIL_PASSWORD', getenv('POP3_PASSWORD'));
define('POSTIE_USE_SSL', getenv('POP3_SSL') === 'true');

// Define WP_CONTENT directory to be at the root while core files remain in wp folder
define('WP_CONTENT_DIR', dirname(__FILE__) . '/' . getenv('WP_CONTENT_DIR'));
define('WP_CONTENT_URL', getenv('WP_CONTENT_URL'));

// Redis configuration
define('WP_REDIS_HOST', getenv('REDIS_HOST'));
define('WP_REDIS_PORT', getenv('REDIS_PORT'));
define('WP_REDIS_PASSWORD', getenv('REDIS_PASSWORD'));
define('WP_REDIS_CLIENT', getenv('WP_REDIS_CLIENT'));
define('WP_REDIS_DATABASE', getenv('WP_REDIS_DATABASE'));
define('WP_REDIS_TIMEOUT', getenv('WP_REDIS_TIMEOUT'));
define('WP_REDIS_READ_TIMEOUT', getenv('WP_REDIS_READ_TIMEOUT'));
define('WP_REDIS_EXPIRE', getenv('WP_REDIS_EXPIRE'));
define('WP_REDIS_MAXTTL', getenv('WP_REDIS_MAXTTL'));
define('WP_CACHE', getenv('WP_CACHE'));
define('WP_ENABLE_REDIS_CACHE', getenv('WP_ENABLE_REDIS_CACHE') === 'true');

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/wp/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
