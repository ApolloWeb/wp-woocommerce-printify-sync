<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Start output buffering
ob_start();

// Add this at the end of the file
register_shutdown_function(function() {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
});
