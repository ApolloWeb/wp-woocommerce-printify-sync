<?php

require_once(dirname(__FILE__) . '/wp-load.php');
require_once(dirname(__FILE__) . '/wp-includes/plugin.php');
require_once(dirname(__FILE__) . '/wp-includes/pluggable.php');

// Force HTTPS when using Ngrok
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// Load environment variables

/** MySQL settings */
define('DB_NAME', getenv('WORDPRESS_DB_NAME'));
define('DB_USER', getenv('WORDPRESS_DB_USER'));
define('DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD'));
define('DB_HOST', getenv('WORDPRESS_DB_HOST'));

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
$table_prefix = 'wp_';

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

// Force SMTP settings in WordPress
add_action('phpmailer_init', function ($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host = SMTP_HOST;
    $phpmailer->Port = SMTP_PORT;
    $phpmailer->SMTPAuth = SMTP_AUTH;
    $phpmailer->Username = SMTP_USER;
    $phpmailer->Password = SMTP_PASS;
    $phpmailer->SMTPSecure = SMTP_SECURE;
    $phpmailer->From = SMTP_FROM;
    $phpmailer->FromName = SMTP_NAME;
});

// Postie (Incoming Mail via POP3)
define('POSTIE_MAIL_SERVER', getenv('POP3_HOST'));
define('POSTIE_MAIL_PORT', getenv('POP3_PORT'));
define('POSTIE_MAIL_USER', getenv('POP3_USERNAME'));
define('POSTIE_MAIL_PASSWORD', getenv('POP3_PASSWORD'));
define('POSTIE_USE_SSL', getenv('POP3_SSL') === 'true');

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/wp/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
