<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= __('Printify Shipping Profiles', 'wp-woocommerce-printify-sync') ?></h5>
        <button id="wpwps-sync-shipping" class="btn btn-primary">
            <i class="fas fa-sync-alt me-1"></i> <?= __('Sync Profiles', 'wp-woocommerce-printify-sync') ?>
        </button>
    </div>
    <div class="card-body">
        <p><?= __('Shipping profiles are pulled from Printify and used to calculate shipping costs for products.', 'wp-woocommerce-printify-sync') ?></p>
        
        <?php if (empty($profiles)): ?>
        <div class="alert alert-info">
            <?= __('No shipping profiles found. Click "Sync Profiles" to import profiles from Printify.', 'wp-woocommerce-printify-sync') ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?= __('Provider', 'wp-woocommerce-printify-sync') ?></th>
                        <th><?= __('Profile', 'wp-woocommerce-printify-sync') ?></th>
                        <th><?= __('Countries', 'wp-woocommerce-printify-sync') ?></th>
                        <th><?= __('First Item', 'wp-woocommerce-printify-sync') ?></th>
                        <th><?= __('Additional Item', 'wp-woocommerce-printify-sync') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($profiles as $profile): ?>
                    <tr>
                        <td><?= esc_html($profile['provider_name']) ?></td>
                        <td><?= esc_html($profile['name']) ?></td>
                        <td>
                            <?php 
                            $countries = array_column($profile['countries'], 'code');
                            echo count($countries) > 5 
                                ? sprintf(__('%d countries', 'wp-woocommerce-printify-sync'), count($countries))
                                : implode(', ', array_slice($countries, 0, 5));
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($profile['pricing'])) {
                                $first_price = reset($profile['pricing']);
                                echo wc_price($first_price['first_item']);
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($profile['pricing'])) {
                                $first_price = reset($profile['pricing']);
                                echo wc_price($first_price['additional_item']);
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <p><strong><?= __('Last Synced:', 'wp-woocommerce-printify-sync') ?></strong> <?= date_i18n(get_option('date_format') . ' ' . get_option('time_format'), get_option('wpwps_shipping_last_sync', time())) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><?= __('Shipping Zone Integration', 'wp-woocommerce-printify-sync') ?></h5>
    </div>
    <div class="card-body">
        <p><?= __('Printify Shipping Zones are created automatically when you sync shipping profiles. These zones match the countries supported by your Printify print providers.', 'wp-woocommerce-printify-sync') ?></p>
        
        <a href="<?= admin_url('admin.php?page=wc-settings&tab=shipping') ?>" class="btn btn-outline-primary">
            <i class="fas fa-cog me-1"></i> <?= __('Manage Shipping Zones', 'wp-woocommerce-printify-sync') ?>
        </a>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Sync shipping profiles
    $('#wpwps-sync-shipping').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> <?= esc_js(__('Syncing...', 'wp-woocommerce-printify-sync')) ?>');
        
        WPWPS.api.post('sync_shipping_profiles')
            .then(function(response) {
                if (response.success) {
                    WPWPS.toast.success(response.data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    WPWPS.toast.error(response.data.message || '<?= esc_js(__('Error syncing shipping profiles', 'wp-woocommerce-printify-sync')) ?>');
                    button.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i> <?= esc_js(__('Sync Profiles', 'wp-woocommerce-printify-sync')) ?>');
                }
            })
            .catch(function(error) {
                WPWPS.toast.error('<?= esc_js(__('Error syncing shipping profiles', 'wp-woocommerce-printify-sync')) ?>');
                button.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i> <?= esc_js(__('Sync Profiles', 'wp-woocommerce-printify-sync')) ?>');
                console.error(error);
            });
    });
});
</script>
