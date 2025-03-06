<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class LogViewer {

    public static function renderLogViewer() {
        ?>
        <div class="wrap">
            <h1><?php _e('Log Viewer', 'wp-woocommerce-printify-sync'); ?></h1>
            <form method="post" action="">
                <input type="text" name="log_search" placeholder="<?php _e('Search logs...', 'wp-woocommerce-printify-sync'); ?>" />
                <select name="log_type">
                    <option value=""><?php _e('All Types', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="info"><?php _e('Info', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="error"><?php _e('Error', 'wp-woocommerce-printify-sync'); ?></option>
                    <!-- Add other log types as needed -->
                </select>
                <button type="submit"><?php _e('Filter', 'wp-woocommerce-printify-sync'); ?></button>
            </form>
            <div class="log-results">
                <?php self::displayLogs(); ?>
            </div>
        </div>
        <?php
    }

    public static function displayLogs() {
        $search = isset($_POST['log_search']) ? sanitize_text_field($_POST['log_search']) : '';
        $type = isset($_POST['log_type']) ? sanitize_text_field($_POST['log_type']) : '';

        $logs = Logger::getLogs($search, $type);
        foreach ($logs as $log) {
            echo "<div class='log-entry'>{$log->date} [{$log->type}] {$log->message}</div>";
        }
    }
}