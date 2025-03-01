<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Helper class for admin functionality
 */
class AdminHelper {
    /**
     * Get formatted current date and time in UTC
     *
     * @return string Formatted date and time
     */
    public static function getCurrentDateTime() {
        return gmdate('Y-m-d H:i:s');
    }
    
    /**
     * Get current user's login
     *
     * @return string Current user's login
     */
    public static function getCurrentUserLogin() {
        $user = wp_get_current_user();
        return $user->user_login;
    }
    
    /**
     * Get plugin asset URL
     *
     * @param string $path Relative path to asset
     * @return string Full URL to asset
     */
    public static function getAssetUrl($path) {
        return plugins_url($path, WPWPS_PLUGIN_BASENAME);
    }
    
    /**
     * Get current page slug
     *
     * @param string $hook Admin page hook
     * @return string Page slug
     */
    public static function getCurrentPageSlug($hook) {
        if (strpos($hook, 'wp-woocommerce-printify-sync-shops') !== false) {
            return 'shops';
        } elseif (strpos($hook, 'wp-woocommerce-printify-sync-products') !== false) {
            return 'products';
        }
        return 'settings';
    }
    
    /**
     * Format message for display
     *
     * @param string $message Message text
     * @param string $type Message type (success, error, warning, info)
     * @return string Formatted message HTML
     */
    public static function formatMessage($message, $type = 'info') {
        $icon = '';
        
        switch ($type) {
            case 'success':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
                break;
            case 'error':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
                break;
            case 'warning':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
                break;
            case 'info':
            default:
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
                break;
        }
        
        return sprintf(
            '<div class="wpwps-message wpwps-message-%s"><div class="wpwps-message-icon">%s</div><div class="wpwps-message-content">%s</div></div>',
            esc_attr($type),
            $icon,
            $message
        );
    }
}