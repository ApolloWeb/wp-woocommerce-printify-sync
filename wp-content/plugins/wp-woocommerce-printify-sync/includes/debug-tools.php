<?php
/**
 * Debug Tools for WPWPS Plugin
 * Only loaded when WP_DEBUG is true
 */

// Only execute in admin
if (!is_admin()) {
    return;
}

// Add AJAX debug info
add_action('admin_footer', 'wpwps_debug_footer_info');

function wpwps_debug_footer_info() {
    // Only show on plugin pages
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'wpwps-') === false) {
        return;
    }
    
    // Only show when debug is enabled
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    echo '<div id="wpwps-debug-panel" style="position: fixed; bottom: 0; right: 0; width: 300px; background: #f0f0f0; border: 1px solid #ccc; padding: 10px; z-index: 9999; font-size: 12px; max-height: 200px; overflow: auto; display: none;">';
    echo '<h4>Debug Info</h4>';
    echo '<div id="wpwps-debug-content"></div>';
    echo '</div>';
    
    echo '<script>
    (function($) {
        // Create toggle button
        var $button = $("<button>", {
            text: "Debug",
            style: "position: fixed; bottom: 0; right: 0; z-index: 10000; background: #f0f0f0; border: 1px solid #ccc; padding: 5px 10px;"
        }).appendTo("body");
        
        // Toggle debug panel
        $button.on("click", function() {
            $("#wpwps-debug-panel").toggle();
        });
        
        // Add debug messages
        window.wpwpsDebug = function(msg) {
            var $content = $("#wpwps-debug-content");
            var time = new Date().toTimeString().split(" ")[0];
            $content.prepend("<p><strong>" + time + ":</strong> " + msg + "</p>");
        };
        
        // Capture and log AJAX requests
        $(document).ajaxSend(function(event, xhr, settings) {
            if (settings.url.indexOf("printify_sync") > -1) {
                wpwpsDebug("AJAX Request: " + settings.type + " " + settings.url);
            }
        });
        
        // Capture and log AJAX responses
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.url.indexOf("printify_sync") > -1) {
                var response;
                try {
                    response = JSON.parse(xhr.responseText);
                    wpwpsDebug("AJAX Response: " + (response.success ? "Success" : "Error"));
                } catch (e) {
                    wpwpsDebug("AJAX Response: Invalid JSON");
                }
            }
        });
    })(jQuery);
    </script>';
}
