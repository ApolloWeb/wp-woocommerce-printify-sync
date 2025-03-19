<div class="row">
    <div class="col-12">
        <div class="card wpwps-card w-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-box"></i> Printify Products</h5>
                <div>
                    <button type="button" class="btn btn-primary btn-sm me-2" id="fetch-products">
                        <i class="fas fa-sync"></i> Fetch Products
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="import-selected" disabled>
                        <i class="fas fa-download"></i> Import Selected
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="products-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all"></th>
                                <th style="width: 80px">Image</th>
                                <th>Title</th>
                                <th>Printify ID</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center">Click "Fetch Products" to load products from Printify</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted" id="products-count">
                            Showing <span id="showing-start">0</span> to <span id="showing-end">0</span> of <span id="total-products">0</span> products
                        </div>
                        <nav aria-label="Products navigation">
                            <ul class="pagination mb-0" id="products-pagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
