<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class NotificationManager {
    const NOTICE_TRANSIENT = 'wpps_admin_notices';

    public function init(): void {
        add_action('admin_notices', [$this, 'displayNotices']);
    }

    public function addNotice(string $message, string $type = 'info'): void {
        $notices = get_transient(self::NOTICE_TRANSIENT) ?: [];
        $notices[] = [
            'message' => $message,
            'type' => $type
        ];
        set_transient(self::NOTICE_TRANSIENT, $notices, HOUR_IN_SECONDS);
    }

    public function displayNotices(): void {
        $notices = get_transient(self::NOTICE_TRANSIENT);
        if (!$notices) return;

        foreach ($notices as $notice) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type']),
                wp_kses_post($notice['message'])
            );
        }
        delete_transient(self::NOTICE_TRANSIENT);
    }
}
