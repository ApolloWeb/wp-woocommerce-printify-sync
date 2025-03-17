<?php
// ... continuing from previous implementation ...
?>

<p>
    <?php esc_html_e('Next Steps:', 'wp-woocommerce-printify-sync'); ?>
</p>

<ul>
    <li><?php esc_html_e('Our team has been notified and will investigate the issue.', 'wp-woocommerce-printify-sync'); ?></li>
    <li><?php esc_html_e('You will receive an update within 24 hours.', 'wp-woocommerce-printify-sync'); ?></li>
    <li><?php esc_html_e('If urgent assistance is needed, please contact support.', 'wp-woocommerce-printify-sync'); ?></li>
</ul>

<?php do_action('woocommerce_email_footer', $email); ?>