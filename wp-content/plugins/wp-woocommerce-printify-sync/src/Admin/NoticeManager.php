<?php
/**
 * Admin notices manager.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Handles admin notices in a stylish way.
 */
class NoticeManager {
    /**
     * Notices container.
     *
     * @var array
     */
    private static $notices = [];

    /**
     * Initialize the notice manager.
     *
     * @return void
     */
    public static function init() {
        add_action('admin_notices', [__CLASS__, 'displayNotices']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueueScripts']);
    }

    /**
     * Add a notice.
     *
     * @param string $message The notice message.
     * @param string $type    Notice type (success, error, warning, info).
     * @param bool   $dismissible Whether the notice is dismissible.
     * @param string $id      Unique ID for the notice.
     */
    public static function addNotice($message, $type = 'info', $dismissible = true, $id = '') {
        if (empty($id)) {
            $id = 'wpwps_' . md5($message . time());
        }

        self::$notices[$id] = [
            'message'     => $message,
            'type'        => $type,
            'dismissible' => $dismissible,
        ];

        // Store notice in transient for 60 seconds to persist across page loads
        set_transient('wpwps_admin_notice_' . $id, self::$notices[$id], 60);
    }

    /**
     * Display all registered notices.
     *
     * @return void
     */
    public static function displayNotices() {
        // Get notices from transients
        global $wpdb;
        $transients = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_wpwps_admin_notice_%'"
        );

        foreach ($transients as $transient) {
            $id = str_replace('_transient_wpwps_admin_notice_', '', $transient->option_name);
            $notice = get_transient('wpwps_admin_notice_' . $id);
            
            if ($notice) {
                self::$notices[$id] = $notice;
                // Delete transient after retrieving it
                delete_transient('wpwps_admin_notice_' . $id);
            }
        }

        // Display notices
        foreach (self::$notices as $id => $notice) {
            $type = isset($notice['type']) ? $notice['type'] : 'info';
            $dismissible = isset($notice['dismissible']) && $notice['dismissible'] ? 'is-dismissible' : '';
            
            // Map our types to WordPress admin notice classes
            $class = 'notice wpwps-notice ';
            switch ($type) {
                case 'success':
                    $class .= 'notice-success';
                    $icon = 'fa-check-circle';
                    break;
                case 'error':
                    $class .= 'notice-error';
                    $icon = 'fa-exclamation-circle';
                    break;
                case 'warning':
                    $class .= 'notice-warning';
                    $icon = 'fa-exclamation-triangle';
                    break;
                default:
                    $class .= 'notice-info';
                    $icon = 'fa-info-circle';
                    break;
            }
            
            printf(
                '<div class="%1$s %2$s" id="%3$s">
                    <div class="wpwps-notice-content">
                        <i class="fas %4$s"></i>
                        <p>%5$s</p>
                    </div>
                </div>',
                esc_attr($class),
                esc_attr($dismissible),
                esc_attr($id),
                esc_attr($icon),
                wp_kses_post($notice['message'])
            );
        }
        
        // Clear all displayed notices
        self::$notices = [];
    }

    /**
     * Enqueue scripts and styles for notices.
     *
     * @return void
     */
    public static function enqueueScripts() {
        // Add inline styles for notices
        wp_add_inline_style('wpwps-global', '
            .wpwps-notice {
                border-left: 4px solid #96588a;
                padding: 12px 15px;
                position: relative;
                background: #fff;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
                margin: 15px 0;
                border-radius: 0 4px 4px 0;
            }
            
            .wpwps-notice-content {
                display: flex;
                align-items: center;
            }
            
            .wpwps-notice-content i {
                margin-right: 12px;
                font-size: 20px;
            }
            
            .wpwps-notice-content p {
                font-family: "Inter", sans-serif;
                font-size: 14px;
                margin: 0;
                padding: 0;
                color: #333;
            }
            
            .notice-success.wpwps-notice {
                border-left-color: #28a745;
            }
            
            .notice-success.wpwps-notice i {
                color: #28a745;
            }
            
            .notice-error.wpwps-notice {
                border-left-color: #dc3545;
            }
            
            .notice-error.wpwps-notice i {
                color: #dc3545;
            }
            
            .notice-warning.wpwps-notice {
                border-left-color: #ffc107;
            }
            
            .notice-warning.wpwps-notice i {
                color: #ffc107;
            }
            
            .notice-info.wpwps-notice {
                border-left-color: #17a2b8;
            }
            
            .notice-info.wpwps-notice i {
                color: #17a2b8;
            }
        ');
    }
}
