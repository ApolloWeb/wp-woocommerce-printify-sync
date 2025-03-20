<?php
/**
 * AJAX Debugging Tool
 */

// Add a debug endpoint to test AJAX
add_action('wp_ajax_printify_debug', function() {
    check_ajax_referer('wpwps_nonce', 'nonce');
    
    $response = [
        'status' => 'success',
        'message' => 'AJAX endpoint is working',
        'debug' => [
            'post_data' => $_POST,
            'get_data' => $_GET,
            'request_data' => $_REQUEST,
            'ajax_handler_exists' => class_exists('\\ApolloWeb\\WPWooCommercePrintifySync\\Ajax\\AjaxHandler'),
            'printify_api_exists' => class_exists('\\ApolloWeb\\WPWooCommercePrintifySync\\API\\PrintifyAPI'),
            'root_api_exists' => class_exists('\\ApolloWeb\\WPWooCommercePrintifySync\\PrintifyAPI'),
        ]
    ];
    
    wp_send_json($response);
});

// Include this debug script
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_footer', function() {
        ?>
        <script>
        // Debug AJAX functions
        function debugAjax() {
            return jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'printify_debug',
                    nonce: wpwps_data.nonce
                }
            })
            .then(function(response) {
                console.log('Debug AJAX response:', response);
                return response;
            });
        }
        
        // Add debug button when on plugin page
        if (window.location.href.includes('page=wpwps-')) {
            jQuery(document).ready(function($) {
                $('<button id="debug-ajax" class="button">Debug AJAX</button>')
                    .appendTo('#wpbody-content')
                    .css({
                        position: 'fixed',
                        bottom: '20px',
                        right: '20px',
                        zIndex: 9999
                    })
                    .on('click', function() {
                        debugAjax();
                    });
            });
        }
        </script>
        <?php
    });
}
