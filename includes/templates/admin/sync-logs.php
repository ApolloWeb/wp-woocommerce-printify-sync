<div class="wrap">
    <h1>Sync Logs</h1>
    <form method="get" action="">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Filter by Date</th>
                <td>
                    <input type="date" name="log_date" value="<?php echo esc_attr($log_date); ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Filter by Status</th>
                <td>
                    <select name="log_status">
                        <option value="all" <?php selected($log_status, 'all'); ?>>All</option>
                        <option value="success" <?php selected($log_status, 'success'); ?>>Success</option>
                        <option value="error" <?php selected($log_status, 'error'); ?>>Error</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button('Filter Logs'); ?>
    </form>
    <div class="sync-logs-wrap">
        <table class="widefat">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td><?php echo esc_html($log['date']); ?></td>
                        <td><?php echo esc_html($log['status']); ?></td>
                        <td><?php echo esc_html($log['message']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>