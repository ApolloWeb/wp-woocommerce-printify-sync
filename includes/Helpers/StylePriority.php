<?php
/**
 * Style Priority Helper
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class StylePriority
 */
class StylePriority {
    
    /**
     * Init
     */
    public static function init() {
        add_action('admin_footer', [self::class, 'add_style_priority_script']);
    }
    
    /**
     * Add script to ensure our styles have priority
     */
    public static function add_style_priority_script() {
        // Only run on our plugin pages
        $screen = get_current_screen();
        if (!$screen || (strpos($screen->id, 'printify') === false && 
                        strpos($screen->id, 'wp-woocommerce-printify-sync') === false)) {
            return;
        }
        
        ?>
        <script>
        (function() {
            // Ensure our plugin styles take precedence
            const enhancePluginStyles = () => {
                const pluginStyles = document.querySelectorAll('link[rel="stylesheet"][href*="wp-woocommerce-printify-sync"]');
                
                // Move our plugin styles to the end of head to ensure they load last
                pluginStyles.forEach(style => {
                    document.head.appendChild(style);
                });
                
                console.log('Printify Sync: Style priority enforced for ' + pluginStyles.length + ' stylesheets');
            };
            
            // Run once on load
            enhancePluginStyles();
            
            // Also run on any dynamic updates
            const observer = new MutationObserver(enhancePluginStyles);
            observer.observe(document.head, { childList: true });
        })();
        </script>
        <?php
    }
}