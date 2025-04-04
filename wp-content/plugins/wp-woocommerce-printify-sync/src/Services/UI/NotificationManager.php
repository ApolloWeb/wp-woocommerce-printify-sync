<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services\UI;

class NotificationManager {
    private array $notifications = [];

    public function add(string $message, string $type = 'info'): void 
    {
        $this->notifications[] = [
            'message' => $message,
            'type' => $type
        ];
    }

    public function getAll(): array 
    {
        return $this->notifications;
    }

    public function render(): void 
    {
        foreach ($this->notifications as $notification) {
            $class = 'notice notice-' . esc_attr($notification['type']);
            printf(
                '<div class="%1$s"><p>%2$s</p></div>',
                $class,
                esc_html($notification['message'])
            );
        }
    }

    public function clear(): void 
    {
        $this->notifications = [];
    }
}
