<div class="wpwps-card">
    <div class="card-body">
        <!-- Filters -->
        <div class="wpwps-filters mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="order-search" placeholder="Search orders...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="order-status">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="printify-status">
                        <option value="">All Printify Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_production">In Production</option>
                        <option value="shipped">Shipped</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" id="order-date">
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="table-responsive">
            <table class="wpwps-table" id="orders-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Printify Status</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Orders will be loaded via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="wpwps-pagination mt-4">
            <!-- Pagination will be loaded via AJAX -->
        </div>
    </div>
</div>
