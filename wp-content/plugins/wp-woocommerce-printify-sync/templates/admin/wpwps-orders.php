<div class="row">
    <div class="col-12">
        <div class="card wpwps-card w-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-shopping-cart"></i> Order Synchronization</h5>
                <div>
                    <button type="button" class="btn btn-success btn-sm" id="sync-orders">
                        <i class="fas fa-sync-alt"></i> Sync Orders
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="orders-table">
                        <thead>
                            <tr>
                                <th>WC Order #</th>
                                <th>Printify Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Shipping Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Orders will be loaded here via AJAX -->
                            <tr>
                                <td colspan="8" class="text-center">Loading orders...</td>
                            </tr>
                        </tbody>
                    </table>
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
</div>
