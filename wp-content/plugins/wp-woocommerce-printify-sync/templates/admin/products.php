<div class="wpwps-card">
    <div class="card-body">
        <!-- Filters -->
        <div class="wpwps-filters mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="product-search" placeholder="Search products...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="product-category">
                        <option value="">All Categories</option>
                        <?php foreach (get_terms(['taxonomy' => 'product_cat']) as $term): ?>
                            <option value="<?php echo esc_attr($term->term_id); ?>">
                                <?php echo esc_html($term->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="sync-status">
                        <option value="">All Status</option>
                        <option value="synced">Synced</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="button" class="btn btn-primary" id="sync-selected">
                        <i class="fas fa-sync me-2"></i> Sync Selected
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="table-responsive">
            <table class="wpwps-table" id="products-table">
                <thead>
                    <tr>
                        <th width="20"><input type="checkbox" class="select-all"></th>
                        <th width="80">Image</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Status</th>
                        <th>Last Sync</th>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Products will be loaded via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="wpwps-pagination mt-4">
            <!-- Pagination will be loaded via AJAX -->
        </div>
    </div>
</div>
