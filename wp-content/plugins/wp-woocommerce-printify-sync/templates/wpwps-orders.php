<div class="row">
    <div class="col-12">
        <div class="card wpwps-card w-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-shopping-cart"></i> Printify Orders</h5>
                <div>
                    <button type="button" class="btn btn-danger btn-sm me-2" id="clear-cache">
                        <i class="fas fa-trash"></i> Clear Cache
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="fetch-orders">
                        <i class="fas fa-sync"></i> Fetch Orders
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Alert container -->
                <div id="orders-alerts" class="mb-3">
                    <?php if (isset($cache_cleared) && $cache_cleared): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        The orders cache has been automatically cleared. Click "Fetch Orders" to load fresh data from Printify.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Orders table -->
                <div class="table-responsive">
                    <table class="table table-striped" id="orders-table">
                        <thead>
                            <tr>
                                <th>Printify ID</th>
                                <th>WooCommerce Order</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Shipping Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-center">Click "Fetch Orders" to load orders from Printify</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted" id="orders-count">
                        Showing <span id="showing-start">0</span> to <span id="showing-end">0</span> of <span id="total-orders">0</span> orders
                    </div>
                    <nav aria-label="Orders navigation">
                        <ul class="pagination mb-0" id="orders-pagination"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>