<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Toast Notification Manager
 */
class ToastManager {
    /**
     * Enqueue toast scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            WPWPS_ASSET_PREFIX . 'toasts',
            WPWPS_ASSETS_URL . 'js/' . WPWPS_ASSET_PREFIX . 'toasts.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
        
        wp_localize_script(
            WPWPS_ASSET_PREFIX . 'toasts',
            'wpwps_toasts',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpwps_toast_nonce')
            ]
        );
    }
    
    /**
     * Register AJAX endpoints for toast notifications
     */
    public function register_ajax_endpoints() {
        add_action('wp_ajax_wpwps_add_toast', [$this, 'ajax_add_toast']);
    }
    
    /**
     * AJAX handler for adding a toast
     */
    public function ajax_add_toast() {
        check_ajax_referer('wpwps_toast_nonce', 'nonce');
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'info';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 5000;
        
        $this->add_toast($type, $title, $message, $duration);
        
        wp_send_json_success();
    }
    
    /**
     * Add a toast notification to the session
     *
     * @param string $type Type of toast: success, error, warning, info
     * @param string $title Toast title
     * @param string $message Toast message
     * @param int $duration Duration in milliseconds
     */
    public function add_toast($type, $title, $message, $duration = 5000) {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['wpwps_toasts'])) {
            $_SESSION['wpwps_toasts'] = [];
        }
        
        $_SESSION['wpwps_toasts'][] = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'duration' => $duration
        ];
    }
    
    /**
     * Get and clear all toasts from the session
     *
     * @return array
     */
    public function get_toasts() {
        if (!session_id()) {
            session_start();
        }
        
        $toasts = isset($_SESSION['wpwps_toasts']) ? $_SESSION['wpwps_toasts'] : [];
        
        // Clear toasts
        $_SESSION['wpwps_toasts'] = [];
        
        return $toasts;
    }
    
    /**
     * Render toast container and initial toasts
     */
    public function render_toasts() {
        $toasts = $this->get_toasts();
        
        if (!empty($toasts)) {
            ?>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof wpwpsToastManager !== 'undefined') {
                        <?php foreach($toasts as $toast): ?>
                            wpwpsToastManager.showToast(
                                '<?php echo esc_js($toast['type']); ?>',
                                '<?php echo esc_js($toast['title']); ?>',
                                '<?php echo esc_js($toast['message']); ?>',
                                <?php echo esc_js($toast['duration']); ?>
                            );
                        <?php endforeach; ?>
                    }
                });
            </script>
            <?php
        }
    }
}
