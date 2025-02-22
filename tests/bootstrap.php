<?php

// Load WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR') ?: '/var/www/tests';

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', function () {
    require dirname(__DIR__) . '/wp-content/plugins/wp-woocommerce-printify-sync/plugin.php';
});

require $_tests_dir . '/includes/bootstrap.php';
