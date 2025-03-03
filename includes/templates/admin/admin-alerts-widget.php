<div class="widget-content">
    <h3>Admin Alerts</h3>
    <ul>
        <?php foreach ($alerts as $alert) : ?>
            <li>
                <strong><?php echo esc_html($alert['type']); ?>:</strong>
                <?php echo esc_html($alert['message']); ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>