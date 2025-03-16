<div class="printify-logs">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">System Logs</h5>
            <div class="btn-group">
                <button class="btn btn-light btn-sm" id="refreshLogs">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button class="btn btn-light btn-sm" id="exportLogs">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchLogs" placeholder="Search logs...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="logLevel">
                        <option value="">All Levels</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text">From</span>
                        <input type="date" class="form-control" id="dateFrom">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text">To</span>
                        <input type="date" class="form-control" id="dateTo">
                    </div>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100" id="applyFilters">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="table-responsive">
                <table class="table table-hover" id="logsTable">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Level</th>
                            <th>Message</th>
                            <th>Context</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated via JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav aria-label="Log navigation" class="mt-4">
                <ul class="pagination justify-content-center" id="logPagination">
                    <!-- Populated via JavaScript -->
                </ul>
            </nav>
        </div>
    </div>

    <!-- Log Detail Modal -->
    <div class="modal fade" id="logDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Log Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Populated via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>