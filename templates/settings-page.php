<div class="wrap">
    <h1><?php esc_html_e( 'Printify Sync Settings', 'wp-woocommerce-printify-sync' ); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields( 'printify-sync' );
        do_settings_sections( 'printify-sync' );
        submit_button();
        ?>
    </form>
</div>