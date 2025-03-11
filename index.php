<?php
// Load Composer Autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) { // Load Composer autoloader if it exists
    require_once __DIR__ . '/vendor/autoload.php'; // Load Composer autoloader
}

// Load Environment Variables
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) { // Load .env file if it exists
    $dotenv = Dotenv::createImmutable(__DIR__); // Load .env file
    $dotenv->load(); // Parse the .env file   
}

define('WP_USE_THEMES', true); // Enable WordPress theme support
require __DIR__ . '/wp/wp-blog-header.php'; // Load WordPress
