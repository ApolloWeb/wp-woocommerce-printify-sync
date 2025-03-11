<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) { // Load Composer autoloader if it exists
    require_once __DIR__ . '/vendor/autoload.php'; // Load Composer autoloader
}

// Load Environment Variables
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) { // Load .env file if it exists
    $dotenv = Dotenv::createImmutable(__DIR__); // Load .env file
    $dotenv->load(); // Parse the .env file   
}

// ** Database settings - These settings get pulled from your environment file ** //
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_HOST', getenv('DB_HOST'));
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
define('DB_COLLATE', getenv('DB_COLLATE') ?: 'utf8mb4_unicode_ci');
define('DB_ROOT_PASSWORD', getenv('DB_ROOT_PASSWORD'));

// Load WordPress admin user details from .env
define('WP_ADMIN_USER', getenv('WP_ADMIN_USER') ?: 'admin');
define('WP_ADMIN_PASSWORD', getenv('WP_ADMIN_PASSWORD') ?: 'securepassword');
define('WP_ADMIN_EMAIL', getenv('WP_ADMIN_EMAIL') ?: 'admin@example.com');

// ** WordPress Table Prefix ** //
$table_prefix = getenv('DB_TABLE_PREFIX') ?: 'wp_';

// Debugging mode (useful for troubleshooting)
define('WP_DEBUG', filter_var(getenv('WP_DEBUG'), FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_LOG', filter_var(getenv('WP_DEBUG_LOG'), FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_DISPLAY', filter_var(getenv('WP_DEBUG_DISPLAY'), FILTER_VALIDATE_BOOLEAN));

// Redis Configuration
define('WP_REDIS_HOST', 'redis'); // Use 'redis' for Docker, or '127.0.0.1' for local
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_DATABASE', 0);
define('WP_REDIS_TIMEOUT', 1);
define('WP_REDIS_READ_TIMEOUT', 1);
define('WP_CACHE', true); // Enable WordPress caching

// Redis Socket Path (Optional - for Unix socket connections)
define('WP_REDIS_PATH', '/var/run/redis/redis-server.sock'); // Change path if needed

// Define Woocommerce session handler
define('WC_SESSION_HANDLER', 'Redis');

// Security Keys (auto-populated from .env)
define('AUTH_KEY', getenv('AUTH_KEY'));
define('SECURE_AUTH_KEY', getenv('SECURE_AUTH_KEY'));
define('LOGGED_IN_KEY', getenv('LOGGED_IN_KEY'));
define('NONCE_KEY', getenv('NONCE_KEY'));
define('AUTH_SALT', getenv('AUTH_SALT'));
define('SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT'));
define('LOGGED_IN_SALT', getenv('LOGGED_IN_SALT'));
define('NONCE_SALT', getenv('NONCE_SALT'));

// WordPress URLs
define('WP_HOME', 'http://localhost:8080');
define('WP_SITEURL', 'http://localhost:8080');

// Default theme
define('WP_DEFAULT_THEME', 'botiga');

require_once ABSPATH . 'wp-settings.php';
