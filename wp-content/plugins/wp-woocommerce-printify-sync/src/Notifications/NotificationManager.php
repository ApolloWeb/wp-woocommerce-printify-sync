<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Notifications;

class NotificationManager
{
    private const NOTIFICATION_TRANSIENT = 'wpwps_notifications';
    private const NOTIFICATION_USER_META = 'wpwps_dismissed_notifications';

    public function addNotification(string $message, string $type = 'info', array $options = []): void
    {
        $notifications = $this->getNotifications();
        
        $notifications[] = [
            'id' => uniqid('wpwps_'),
            'message' => $message,
            'type' => $type,
            'dismissible' => $options['dismissible'] ?? true,
            'expiry' => isset($options['expiry']) ? time() + $options['expiry'] : null,
            'created_at' => time(),
            'context' => $options['context'] ?? 'global',
            'actions' => $options['actions'] ?? [],
        ];

        set_transient(self::NOTIFICATION_TRANSIENT, $notifications, DAY_IN_SECONDS);
    }

    public function getNotifications(): array
    {
        $notifications = get_transient(self::NOTIFICATION_TRANSIENT) ?: [];
        $user_id = get_current_user_id();
        $dismissed = get_user_meta($user_id, self::NOTIFICATION_USER_META, true) ?: [];

        return array_filter($notifications, function($notification) use ($dismissed) {
            // Remove if dismissed
            if (in_array($notification['id'], $dismissed)) {
                return false;
            }

            // Remove if expired
            if (isset($notification['expiry']) && $notification['expiry'] < time()) {
                return false;
            }

            return true;
        });
    }

    public function dismissNotification(string $notification_id): bool
    {
        $user_id = get_current_user_id();
        $dismissed = get_user_meta($user_id, self::NOTIFICATION_USER_META, true) ?: [];
        
        if (!in_array($notification_id, $dismissed)) {
            $dismissed[] = $notification_id;
            update_user_meta($user_id, self::NOTIFICATION_USER_META, $dismissed);
        }

        return true;
    }
}