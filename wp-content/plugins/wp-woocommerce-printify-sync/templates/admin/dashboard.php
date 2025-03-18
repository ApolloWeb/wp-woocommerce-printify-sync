<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Printify Dashboard', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <div class="wpwps-dashboard">
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo esc_html($data['stats']['products']); ?></h3>
                        <p><?php _e('Synced Products', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <a href="<?php echo admin_url('admin.php?page=printify-sync-import'); ?>" class="small-box-footer">
                        <?php _e('Import Products', 'wp-woocommerce-printify-sync'); ?> 
                        <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo esc_html($data['stats']['last_sync']); ?></h3>
                        <p><?php _e('Last Sync', 'wp-woocommerce-printify-sync'); ?></p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sync"></i>
                    </div>
                    <a href="#" class="small-box-footer" id="wpwps-sync-now">
                        <?php _e('Sync Now', 'wp-woocommerce-printify-sync'); ?> 
                        <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- Add more stat boxes as needed -->
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php _e('Recent Orders', 'wp-woocommerce-printify-sync'); ?></h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table m-0">
                        <thead>
                            <tr>
                                <th><?php _e('Order', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php _e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php _e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['recent_orders'] as $order) : ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($order->ID); ?>">
                                            #<?php echo esc_html($order->ID); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html(get_post_time('F j, Y', false, $order->ID)); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo esc_attr($this->get_status_class($order->printify_status)); ?>">
                                            <?php echo esc_html($order->printify_status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($order->ID); ?>" class="btn btn-xs btn-info">
                                            <i class="fas fa-eye"></i> <?php _e('View', 'wp-woocommerce-printify-sync'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
