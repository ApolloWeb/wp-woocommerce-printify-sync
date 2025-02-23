<?php
// ** Database settings - You can get this info from your web host ** //
define( 'DB_NAME', getenv('WORDPRESS_DB_NAME') );
define( 'DB_USER', getenv('WORDPRESS_DB_USER') );
define( 'DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD') );
define( 'DB_HOST', getenv('WORDPRESS_DB_HOST') );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// ** Authentication Unique Keys and Salts ** //
define( 'AUTH_KEY',         getenv('WP_AUTH_KEY') );
define( 'SECURE_AUTH_KEY',  getenv('WP_SECURE_AUTH_KEY') );
define( 'LOGGED_IN_KEY',    getenv('WP_LOGGED_IN_KEY') );
define( 'NONCE_KEY',        getenv('WP_NONCE_KEY') );
define( 'AUTH_SALT',        getenv('WP_AUTH_SALT') );
define( 'SECURE_AUTH_SALT', getenv('WP_SECURE_AUTH_SALT') );
define( 'LOGGED_IN_SALT',   getenv('WP_LOGGED_IN_SALT') );
define( 'NONCE_SALT',       getenv('WP_NONCE_SALT') );

$table_prefix = getenv('WORDPRESS_TABLE_PREFIX');

// ** WordPress debugging mode ** //
define( 'WP_DEBUG', getenv('WP_DEBUG') === 'true' );
define( 'WP_DEBUG_LOG', getenv('WP_DEBUG_LOG') === 'true' );
define( 'WP_DEBUG_DISPLAY', getenv('WP_DEBUG_DISPLAY') === 'true' );

// ** WordPress Memory settings ** //
define( 'WP_MEMORY_LIMIT', getenv('PHP_MEMORY_LIMIT') );
define( 'WP_MAX_MEMORY_LIMIT', '256M' );

// ** WordPress Environment Type ** //
define( 'WP_ENVIRONMENT_TYPE', getenv('WP_ENVIRONMENT_TYPE') );

// ** WordPress URLs ** //
define( 'WP_HOME', getenv('WP_HOME') );
define( 'WP_SITEURL', getenv('WP_SITEURL') );

// ** Disable automatic updates ** //
define( 'AUTOMATIC_UPDATER_DISABLED', true );

// ** Disable file editing from WordPress admin ** //
define( 'DISALLOW_FILE_EDIT', true );

// ** That's all, stop editing! Happy publishing. ** //

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';