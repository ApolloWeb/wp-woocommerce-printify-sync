<?php
/**
 * Product Import template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var bool $apiConfigured
 * @var string $shopId
 * @var string $shopName
 * @var int $lastImport
 * @var bool $importRunning
 * @var array $importStats
 * @var array $productTypes
 * @var string $dashboardUrl
 * @var string $importNonce
 * @var string $cancelNonce
 * @var bool $actionSchedulerAvailable
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Calculate progress percentage
$progress = 0;
if ($importStats['total'] > 0) {
    $progress = round(($importStats['processed'] / $importStats['total']) * 100);
}
?>

<div class="wrap wpwps-import-page">
    <?php 
    // Include header
    $this->section('dashboard-header', [
        'apiConfigured' => $apiConfigured,
        'shopId' => $shopId,
        'shopName' => $shopName,
        'settingsUrl' => admin_url('admin.php?page=printify-sync-settings')
    ]); 
    ?>
    
    <?php settings_errors('wpwps_import'); ?>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-cloud-download-alt"></i> <?php echo esc_html__('Import Products from Printify', 'wp-woocommerce-printify-sync'); ?></h5>
        </div>
        <div class="card-body">
            <?php if (!$apiConfigured): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo esc_html__('You need to configure Printify API settings before importing products.', 'wp-woocommerce-printify-sync'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=printify-sync-settings')); ?>" class="btn btn-warning btn-sm ms-3">
                        <i class="fas fa-cog me-1"></i> <?php echo esc_html__('Configure Settings', 'wp-woocommerce-printify-sync'); ?>
                    </a>
                </div>
            <?php elseif (!$actionSchedulerAvailable): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo esc_html__('Action Scheduler library not found. Please ensure WooCommerce is installed and activated. Background product imports will not work without this library.', 'wp-woocommerce-printify-sync'); ?>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo esc_html__('Please make sure WooCommerce is installed and activated, then return to this page.', 'wp-woocommerce-printify-sync'); ?>
                    <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="btn btn-info btn-sm ms-3">
                        <i class="fas fa-plug me-1"></i> <?php echo esc_html__('Go to Plugins', 'wp-woocommerce-printify-sync'); ?>
                    </a>
                </div>
            <?php else: ?>
                <?php if ($importRunning): ?>
                    <div class="import-status-panel">
                        <h5><?php echo esc_html__('Import In Progress', 'wp-woocommerce-printify-sync'); ?></h5>
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?php echo esc_attr($progress); ?>%" aria-valuenow="<?php echo esc_attr($progress); ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo esc_html($progress); ?>%
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <strong><?php echo esc_html__('Products to Import:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <span class="badge bg-primary ms-2"><?php echo esc_html($importStats['total']); ?></span>
                            </div>
                            <div>
                                <strong><?php echo esc_html__('Processed:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <span class="badge bg-info ms-2"><?php echo esc_html($importStats['processed']); ?></span>
                            </div>
                            <div>
                                <strong><?php echo esc_html__('Imported:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <span class="badge bg-success ms-2"><?php echo esc_html($importStats['imported']); ?></span>
                            </div>
                            <div>
                                <strong><?php echo esc_html__('Updated:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <span class="badge bg-warning ms-2"><?php echo esc_html($importStats['updated']); ?></span>
                            </div>
                            <div>
                                <strong><?php echo esc_html__('Failed:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <span class="badge bg-danger ms-2"><?php echo esc_html($importStats['failed']); ?></span>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo esc_html__('Products are being imported in the background. You can leave this page and come back later.', 'wp-woocommerce-printify-sync'); ?>
                        </div>
                        
                        <form method="post" class="mt-3">
                            <?php wp_nonce_field('wpwps_cancel_import_nonce', 'wpwps_import_nonce'); ?>
                            <input type="hidden" name="wpwps_product_import_action" value="cancel_import" />
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-stop-circle me-1"></i> <?php echo esc_html__('Cancel Import', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <form method="post" class="import-form">
                        <?php wp_nonce_field('wpwps_product_import_nonce', 'wpwps_import_nonce'); ?>
                        <input type="hidden" name="wpwps_product_import_action" value="start_import" />
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_type" class="form-label"><?php echo esc_html__('Product Type Filter', 'wp-woocommerce-printify-sync'); ?></label>
                                    <select name="product_type" id="product_type" class="form-select">
                                        <option value=""><?php echo esc_html__('All Product Types', 'wp-woocommerce-printify-sync'); ?></option>
                                        <?php foreach ($productTypes as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text"><?php echo esc_html__('Filter products to import by type', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sync_mode" class="form-label"><?php echo esc_html__('Sync Mode', 'wp-woocommerce-printify-sync'); ?></label>
                                    <select name="sync_mode" id="sync_mode" class="form-select">
                                        <option value="all"><?php echo esc_html__('Import All Products & Update Existing', 'wp-woocommerce-printify-sync'); ?></option>
                                        <option value="new_only"><?php echo esc_html__('Import New Products Only', 'wp-woocommerce-printify-sync'); ?></option>
                                    </select>
                                    <div class="form-text"><?php echo esc_html__('Choose which products to import and sync', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Retrieval Status Section -->
                        <div id="product-retrieval-status" class="mb-4 d-none">
                            <div class="alert alert-info">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="spinner-border spinner-border-sm me-2" role="status">
                                        <span class="visually-hidden"><?php echo esc_html__('Loading...', 'wp-woocommerce-printify-sync'); ?></span>
                                    </div>
                                    <strong id="retrieval-status-message"><?php echo esc_html__('Retrieving products from Printify...', 'wp-woocommerce-printify-sync'); ?></strong>
                                </div>
                                <div class="progress">
                                    <div id="retrieval-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Preview Area -->
                        <div id="product-preview" class="mb-4 d-none">
                            <h5><?php echo esc_html__('Products Ready for Import', 'wp-woocommerce-printify-sync'); ?></h5>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <span id="products-count-message"><?php echo esc_html__('0 products retrieved successfully and ready for import.', 'wp-woocommerce-printify-sync'); ?></span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?php echo esc_html__('Product', 'wp-woocommerce-printify-sync'); ?></th>
                                            <th><?php echo esc_html__('Type', 'wp-woocommerce-printify-sync'); ?></th>
                                            <th><?php echo esc_html__('Variants', 'wp-woocommerce-printify-sync'); ?></th>
                                            <th><?php echo esc_html__('Status', 'wp-woocommerce-printify-sync'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="products-preview-table">
                                        <!-- Preview content will be injected here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mb-4 border-top pt-4">
                            <h5><?php echo esc_html__('Field Mapping Information', 'wp-woocommerce-printify-sync'); ?></h5>
                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?php echo esc_html__('Printify Field', 'wp-woocommerce-printify-sync'); ?></th>
                                            <th><?php echo esc_html__('WooCommerce Field', 'wp-woocommerce-printify-sync'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>product.title</code></td>
                                            <td><?php echo esc_html__('Product Title', 'wp-woocommerce-printify-sync'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>product.description</code></td>
                                            <td><?php echo esc_html__('Long Description', 'wp-woocommerce-printify-sync'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>product.tags</code></td>
                                            <td><?php echo esc_html__('Product Tags', 'wp-woocommerce-printify-sync'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>product_type</code></td>
                                            <td><?php echo esc_html__('Product Categories (Hierarchical)', 'wp-woocommerce-printify-sync'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>variant.options</code></td>
                                            <td><?php echo esc_html__('Product Attributes & Variations', 'wp-woocommerce-printify-sync'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>variant.price</code></td>
                                            <td><?php echo esc_html__('Variation Price', 'wp-woocommerce-printify-sync'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>variant.cost</code></td>
                                            <td><code>_printify_cost_price</code> <?php echo esc_html__('(Meta)', 'wp-woocommerce-printify-sync'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>variant.sku</code></td>
                                            <td><?php echo esc_html__('Variation SKU', 'wp-woocommerce-printify-sync'); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="d-flex">
                            <button type="button" id="retrieve-products" class="btn btn-info me-2">
                                <i class="fas fa-download me-1"></i> <?php echo esc_html__('Retrieve Products', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                            <button type="submit" id="start-import" class="btn btn-primary" disabled>
                                <i class="fas fa-cloud-download-alt me-1"></i> <?php echo esc_html__('Start Import', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                            
                            <?php if ($lastImport): ?>
                                <div class="ms-3 pt-2 text-muted">
                                    <small>
                                        <i class="fas fa-clock"></i> 
                                        <?php echo esc_html__('Last import:', 'wp-woocommerce-printify-sync'); ?> 
                                        <strong><?php echo esc_html(human_time_diff($lastImport, time())); ?></strong> 
                                        <?php echo esc_html__('ago', 'wp-woocommerce-printify-sync'); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?php echo esc_html__('About Product Import', 'wp-woocommerce-printify-sync'); ?></h5>
        </div>
        <div class="card-body">
            <div class="alert alert-light">
                <p><strong><?php echo esc_html__('Initial Import vs. Webhook Updates', 'wp-woocommerce-printify-sync'); ?></strong></p>
                <p><?php echo esc_html__('This page handles the initial import of products from Printify. Once products are imported, any changes made in Printify will be automatically synced to WooCommerce through webhooks.', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
            
            <h5 class="mt-4"><?php echo esc_html__('Import Process Details', 'wp-woocommerce-printify-sync'); ?></h5>
            <ul>
                <li><?php echo esc_html__('Products are imported as WooCommerce variable products', 'wp-woocommerce-printify-sync'); ?></li>
                <li><?php echo esc_html__('Each Printify product variant becomes a WooCommerce variation', 'wp-woocommerce-printify-sync'); ?></li>
                <li><?php echo esc_html__('Product images are downloaded and added to the WordPress media library', 'wp-woocommerce-printify-sync'); ?></li>
                <li><?php echo esc_html__('The import runs in the background using WordPress Action Scheduler, so you can navigate away from this page', 'wp-woocommerce-printify-sync'); ?></li>
                <li><?php echo esc_html__('Each product is connected to its Printify counterpart via meta data, enabling future synchronization', 'wp-woocommerce-printify-sync'); ?></li>
            </ul>
        </div>
    </div>

    <!-- Import Logs Section -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-list-alt"></i> <?php echo esc_html__('Import Logs', 'wp-woocommerce-printify-sync'); ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Time', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php echo esc_html__('Product ID', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php echo esc_html__('Status', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php echo esc_html__('Message', 'wp-woocommerce-printify-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $logs = \ApolloWeb\WPWooCommercePrintifySync\Import\ImportProgressLogger::getLogs(20);
                        if (empty($logs)): 
                        ?>
                            <tr>
                                <td colspan="4" class="text-center"><?php echo esc_html__('No import logs available.', 'wp-woocommerce-printify-sync'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <?php 
                                $statusClass = '';
                                switch ($log['status']) {
                                    case 'success':
                                        $statusClass = 'bg-success';
                                        break;
                                    case 'pending':
                                        $statusClass = 'bg-warning text-dark';
                                        break;
                                    case 'failed':
                                        $statusClass = 'bg-danger';
                                        break;
                                    default:
                                        $statusClass = 'bg-secondary';
                                }
                                ?>
                                <tr>
                                    <td><?php echo esc_html(human_time_diff(strtotime($log['timestamp']), current_time('timestamp')) . ' ' . __('ago', 'wp-woocommerce-printify-sync')); ?></td>
                                    <td><?php echo esc_html($log['product_id']); ?></td>
                                    <td><span class="badge <?php echo esc_attr($statusClass); ?>"><?php echo esc_html(ucfirst($log['status'])); ?></span></td>
                                    <td><?php echo esc_html($log['message']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($logs)): ?>
                <form method="post" class="mt-3">
                    <?php wp_nonce_field('wpwps_clear_logs_nonce', 'wpwps_logs_nonce'); ?>
                    <input type="hidden" name="wpwps_product_import_action" value="clear_logs" />
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash-alt me-1"></i> <?php echo esc_html__('Clear Logs', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
