<div class="wrap">
    <h1><?php _e('Exchange Rates', 'wp-woocommerce-printify-sync'); ?></h1>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-exchange-alt"></i> <?php _e('Manage Exchange Rates', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Currency', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php _e('Exchange Rate', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php _e('Last Updated', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php _e('USD', 'wp-woocommerce-printify-sync'); ?></td>
                                    <td>1.00</td>
                                    <td><?php _e('2025-03-01 12:00:00', 'wp-woocommerce-printify-sync'); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm"><?php _e('Update', 'wp-woocommerce-printify-sync'); ?></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e('EUR', 'wp-woocommerce-printify-sync'); ?></td>
                                    <td>0.85</td>
                                    <td><?php _e('2025-03-01 12:00:00', 'wp-woocommerce-printify-sync'); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm"><?php _e('Update', 'wp-woocommerce-printify-sync'); ?></button>
                                    </td>
                                </tr>
                                <!-- Additional dummy exchange rates can be added here -->
                            </tbody>
                        </table>
                        <button class="btn btn-success"><?php _e('Update All Rates', 'wp-woocommerce-printify-sync'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>