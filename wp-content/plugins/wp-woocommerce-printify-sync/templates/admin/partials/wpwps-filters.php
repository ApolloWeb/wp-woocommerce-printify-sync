<div class="row">
    <div class="col-12">
        <div class="card wpwps-card w-100">
            <div class="card-body">
                <form id="products-filter-form" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="status-filter" class="form-label">Status</label>
                        <select class="form-select" id="status-filter" name="status">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sync-status-filter" class="form-label">Sync Status</label>
                        <select class="form-select" id="sync-status-filter" name="sync_status">
                            <option value="">All</option>
                            <option value="synced">Synced</option>
                            <option value="not_synced">Not Synced</option>
                            <option value="needs_update">Needs Update</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search-products" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search-products" name="search" placeholder="Search products...">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
