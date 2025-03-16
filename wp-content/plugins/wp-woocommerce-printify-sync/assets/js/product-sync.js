// Add this to the ProductSyncDashboard class

bindCleanupEvents() {
    const confirmCheckbox = document.getElementById('confirmCleanup');
    const confirmButton = document.getElementById('confirmCleanupBtn');

    if (confirmCheckbox && confirmButton) {
        confirmCheckbox.addEventListener('change', (e) => {
            confirmButton.disabled = !e.target.checked;
        });

        confirmButton.addEventListener('click', () => this.performCleanup());
    }
}

async performCleanup() {
    try {
        const modal = bootstrap.Modal.getInstance(document.getElementById('cleanupModal'));
        modal.hide();

        const loadingToast = this.showLoadingToast('Cleaning up data...');

        const response = await this.makeRequest('cleanup_printify_data');

        loadingToast.hide();

        if (response.success) {
            this.showNotification('success', 'Data cleanup completed successfully');
            this.showCleanupResults(response.cleaned);
            setTimeout(() => this.refreshDashboard(), 3000);
        } else {
            this.showNotification('error', response.message);
        }
    } catch (error) {
        this.handleError(error);
    }
}

showLoadingToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-info';
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-spinner fa-spin me-2"></i> ${message}
            </div>
        </div>
    `;
    
    document.querySelector('.toast-container').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { autohide: false });
    bsToast.show();
    return bsToast;
}

showCleanupResults(cleaned) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cleanup Results</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Products Deleted
                            <span class="badge bg-primary rounded-pill">${cleaned.products}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Orders Removed
                            <span class="badge bg-primary rounded-pill">${cleaned.orders}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Logs Cleared
                            <span class="badge bg-primary rounded-pill">${cleaned.logs}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Meta Entries Cleaned
                            <span class="badge bg-primary rounded-pill">${cleaned.meta}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Files Removed
                            <span class="badge bg-primary rounded-pill">${cleaned.files}</span>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    modal.addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
}