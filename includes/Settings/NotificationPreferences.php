<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Settings;

class NotificationPreferences
{
    public static function register()
    {
        add_action('show_user_profile', [__CLASS__, 'showNotificationPreferences']);
        add_action('edit_user_profile', [__CLASS__, 'showNotificationPreferences']);
        add_action('personal_options_update', [__CLASS__, 'saveNotificationPreferences']);
        add_action('edit_user_profile_update', [__CLASS__, 'saveNotificationPreferences']);
    }

    public static function showNotificationPreferences($user)
    {
        $preference = get_user_meta($user->ID, 'notification_preference', true);
        ?>
        <h3>Notification Preferences</h3>
        <table class="form-table">
            <tr>
                <th><label for="notification_preference">Preferred Contact Method</label></th>
                <td>
                    <select name="notification_preference" id="notification_preference">
                        <option value="email" <?php selected($preference, 'email'); ?>>Email</option>
                        <option value="sms" <?php selected($preference, 'sms'); ?>>SMS</option>
                        <option value="whatsapp" <?php selected($preference, 'whatsapp'); ?>>WhatsApp</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function saveNotificationPreferences($userId)
    {
        if (!current_user_can('edit_user', $userId)) {
            return false;
        }

        update_user_meta($userId, 'notification_preference', sanitize_text_field($_POST['notification_preference']));
    }
}