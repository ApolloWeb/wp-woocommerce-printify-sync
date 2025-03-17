<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Notification;

class NotificationManager
{
    public function sendProcessStatusEmail(string $process, string $status, array $details): void
    {
        $to = get_option('wpwps_notification_email', get_option('admin_email'));
        $subject = sprintf('[%s] Process Status: %s', get_bloginfo('name'), ucfirst($status));

        $body = $this->generateEmailBody($process, $status, $details);
        $headers = $this->getEmailHeaders();

        wp_mail($to, $subject, $body, $headers);
    }

    public function sendErrorAlert(string $message, array $context = []): void
    {
        $to = get_option('wpwps_notification_email', get_option('admin_email'));
        $subject = sprintf('[%s] Error Alert', get_bloginfo('name'));

        $body = $this->generateErrorEmailBody($message, $context);
        $headers = $this->getEmailHeaders();

        wp_mail($to, $subject, $body, $headers);
    }

    private function generateEmailBody(string $process, string $status, array $details): string
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #674399; color: white; padding: 20px; }
                .content { padding: 20px; }
                .status { font-size: 18px; margin: 20px 0; }
                .status.success { color: #28a745; }
                .status.error { color: #dc3545; }
                .status.warning { color: #ffc107; }
                .details { background: #f8f9fa; padding: 15px; border-radius: 4px; }
                .footer { margin-top: 20px; font-size: 12px; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2><?php echo get_bloginfo('name'); ?> - Process Status Update</h2>
            </div>
            <div class="content">
                <p>A background process has completed execution:</p>
                <div class="status <?php echo strtolower($status); ?>">
                    <strong>Process:</strong> <?php echo esc_html($process); ?><br>
                    <strong>Status:</strong> <?php echo esc_html($status); ?>
                </div>
                <?php if (!empty($details)): ?>
                    <div class="details">
                        <h3>Details:</h3>
                        <ul>
                            <?php foreach ($details as $key => $value): ?>
                                <li><strong><?php echo esc_html($key); ?>:</strong> <?php echo esc_html($value); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            <div class="footer">
                <p>This is an automated message from your WordPress site.</p>
                <p>Time: <?php echo current_time('mysql'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function generateErrorEmailBody(string $message, array $context): string
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #dc3545; color: white; padding: 20px; }
                .content { padding: 20px; }
                .error-message { font-size: 18px; color: #dc3545; margin: 20px 0; }
                .context { background: #f8f9fa; padding: 15px; border-radius: 4px; }
                .footer { margin-top: 20px; font-size: 12px; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2><?php echo get_bloginfo('name'); ?> - Error Alert</h2>
            </div>
            <div class="content">
                <div class="error-message">
                    <?php echo esc_html($message); ?>
                </div>
                <?php if (!empty($context)): ?>
                    <div class="context">
                        <h3>Error Context:</h3>
                        <pre><?php echo esc_html(json_encode($context, JSON_PRETTY_PRINT)); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
            <div class="footer">
                <p>This is an automated message from your WordPress site.</p>
                <p>Time: <?php echo current_time('mysql'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function getEmailHeaders(): array
    {
        return [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
    }
}