<div class="row">
    <div class="col-md-8">
        <div class="card wpwps-card">
            <div class="card-header">
                <h5>Printify API Settings</h5>
            </div>
            <div class="card-body">
                <form id="printify-settings-form">
                    <div class="mb-3">
                        <label for="printify_api_key" class="form-label">API Key</label>
                        <input type="text" class="form-control" id="printify_api_key" name="printify_api_key" 
                            value="<?php echo esc_attr(get_option('wpwps_printify_api_key', '')); ?>" 
                            placeholder="Enter your Printify API key">
                        <div class="form-text">Get your API key from Printify dashboard under Account → API keys</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="printify_endpoint" class="form-label">API Endpoint</label>
                        <input type="url" class="form-control" id="printify_endpoint" name="printify_endpoint" 
                            value="<?php echo esc_attr(get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1')); ?>" 
                            placeholder="Enter Printify API endpoint">
                        <div class="form-text">Default: https://api.printify.com/v1</div>
                    </div>
                    
                    <?php 
                    $shop_id = get_option('wpwps_printify_shop_id', '');
                    $shop_selected = !empty($shop_id);
                    ?>
                    
                    <div class="mb-3">
                        <label for="printify_shop_id" class="form-label">Shop ID</label>
                        <input type="text" class="form-control" id="printify_shop_id" name="printify_shop_id" 
                            value="<?php echo esc_attr($shop_id); ?>" 
                            placeholder="Shop ID will be populated after selection"
                            <?php echo $shop_selected ? 'readonly' : ''; ?>>
                        <?php if (!$shop_selected): ?>
                            <div class="form-text">Use the "Fetch Shops" button below to select a shop</div>
                        <?php else: ?>
                            <div class="form-text">Shop ID is locked after selection</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" id="save-settings" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                        <button type="button" id="test-connection" class="btn btn-secondary" disabled>
                            <i class="fas fa-wifi"></i> Test Connection
                        </button>
                        <?php if (!$shop_selected): ?>
                            <button type="button" id="fetch-shops" class="btn btn-info" disabled>
                                <i class="fas fa-store"></i> Fetch Shops
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
                
                <div id="alerts-container" class="mt-3"></div>
                
                <?php if (!$shop_selected): ?>
                <div id="shops-container" class="mt-4" style="display: none;">
                    <h5>Available Shops</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="shops-table">
                            <thead>
                                <tr>
                                    <th>Shop ID</th>
                                    <th>Title</th>
                                    <th>Connection</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card wpwps-card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-box"></i> Sync State Products</h5>
            </div>
            <div class="card-body">
                <p><strong>Last Products Sync:</strong> <span id="last-sync"><?php echo get_option('wpwps_last_sync', 'Never'); ?></span></p>
                <p><strong>Products Synced:</strong> <span id="products-synced"><?php echo get_option('wpwps_products_synced', '0'); ?></span></p>
                
                <div class="d-grid gap-2">
                    <button type="button" id="manual-sync" class="btn btn-success">
                        <i class="fas fa-sync-alt"></i> Sync Products
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card wpwps-card">
            <div class="card-header">
                <h5><i class="fas fa-shopping-cart"></i> Sync State Orders</h5>
            </div>
            <div class="card-body">
                <p><strong>Last Orders Sync:</strong> <span id="last-orders-sync"><?php echo get_option('wpwps_last_orders_sync', 'Never'); ?></span></p>
                <p><strong>Orders Synced:</strong> <span id="orders-synced"><?php echo get_option('wpwps_orders_synced', '0'); ?></span></p>
                
                <div class="d-grid gap-2">
                    <button type="button" id="manual-sync-orders" class="btn btn-success">
                        <i class="fas fa-sync-alt"></i> Sync Orders
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
