<table class="widefat fixed">
    <thead>
        <tr>
            <th>ID</th>
            <th>Request</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($api_calls as $call) : ?>
            <tr>
                <td><?php echo esc_html($call['id']); ?></td>
                <td><?php echo esc_html($call['request']); ?></td>
                <td><?php echo esc_html($call['status']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>