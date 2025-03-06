<div class="wrap">
    <h1><?php _e('Support Tickets', 'wp-woocommerce-printify-sync'); ?></h1>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-ticket-alt"></i> <?php _e('Manage Support Tickets', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Ticket ID', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php _e('Subject', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php _e('Date Created', 'wp-woocommerce-printify-sync'); ?></th>
                                    <th><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td><?php _e('Sample Ticket 1', 'wp-woocommerce-printify-sync'); ?></td>
                                    <td><?php _e('Open', 'wp-woocommerce-printify-sync'); ?></td>
                                    <td><?php _e('2025-03-01', 'wp-woocommerce-printify-sync'); ?></td>
                                    <td>
                                        <a href="#" class="btn btn-primary btn-sm"><?php _e('View', 'wp-woocommerce-printify-sync'); ?></a>
                                        <a href="#" class="btn btn-danger btn-sm"><?php _e('Close', 'wp-woocommerce-printify-sync'); ?></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td><?php _e('Sample Ticket 2', 'wp-woocommerce-printify-sync'); ?></td>
                                    <td><?php _e('Closed', 'wp-woocommerce-printify-sync'); ?></td>
                                    <td><?php _e('2025-02-28', 'wp-woocommerce-printify-sync'); ?></td>
                                    <td>
                                        <a href="#" class="btn btn-primary btn-sm"><?php _e('View', 'wp-woocommerce-printify-sync'); ?></a>
                                    </td>
                                </tr>
                                <!-- Additional dummy tickets can be added here -->
                            </tbody>
                        </table>
                        <a href="#" class="btn btn-success"><?php _e('Create New Ticket', 'wp-woocommerce-printify-sync'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>