<!-- Add this inside the Sync Actions card -->
<div class="card-body">
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Sync Actions</h5>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" id="syncSelected">
                <i class="fas fa-sync"></i> Sync Selected
            </button>
            <button type="button" class="btn btn-outline-primary" id="syncAll">
                <i class="fas fa-sync-alt"></i> Sync All
            </button>
            <button type="button" class="btn btn-outline-secondary" id="clearQueue">
                <i class="fas fa-broom"></i> Clear Queue
            </button>
            <?php if (current_user_can('manage_options')): ?>
                <button type="button" class="btn btn-danger" id="cleanupData" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                    <i class="fas fa-trash-alt"></i> Cleanup Data
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add this at the end of the template -->
<!-- Cleanup Confirmation Modal -->
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">⚠️ Cleanup Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>Warning!</strong> This action will:
                    <ul class="mb-0">
                        <li>Delete all Printify-synced products</li>
                        <li>Remove all Printify-related orders</li>
                        <li>Clear all sync logs</li>
                        <li>Delete all stored files</li>
                        <li>Reset all Printify metadata</li>
                    </ul>
                </div>
                <p class="text-danger">This action cannot be undone!</p>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmCleanup">
                    <label class="form-check-label" for="confirmCleanup">
                        I understand that this will permanently delete all Printify data
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmCleanupBtn" disabled>
                    <i class="fas fa-trash-alt"></i> Confirm Cleanup
                </button>
            </div>
        </div>
    </div>
</div>