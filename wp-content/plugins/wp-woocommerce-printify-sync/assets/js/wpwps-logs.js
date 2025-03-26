document.addEventListener('DOMContentLoaded', function() {
    initializeLogViewer();
});

function initializeLogViewer() {
    let currentPage = 1;
    let currentFilters = {
        status: '',
        endpoint: '',
        date_from: '',
        date_to: '',
        per_page: 20
    };

    // Initialize filters
    const logStatus = document.getElementById('logStatus');
    const logEndpoint = document.getElementById('logEndpoint');
    const logDateFrom = document.getElementById('logDateFrom');
    const logDateTo = document.getElementById('logDateTo');
    const logsPerPage = document.getElementById('logsPerPage');

    // Load initial data
    loadLogTypes();
    loadEndpoints();
    loadLogs();

    // Event listeners
    logStatus.addEventListener('change', updateLogs);
    logEndpoint.addEventListener('change', updateLogs);
    logDateFrom.addEventListener('change', updateLogs);
    logDateTo.addEventListener('change', updateLogs);
    logsPerPage.addEventListener('change', updateLogs);

    function loadLogTypes() {
        fetch(wpwps.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'wpwps_get_log_types',
                nonce: wpwps.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.types.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type;
                    option.textContent = type.charAt(0).toUpperCase() + type.slice(1);
                    logStatus.appendChild(option);
                });
            }
        });
    }

    function loadEndpoints() {
        fetch(wpwps.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'wpwps_get_log_endpoints',
                nonce: wpwps.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.endpoints.forEach(endpoint => {
                    const option = document.createElement('option');
                    option.value = endpoint;
                    option.textContent = endpoint;
                    logEndpoint.appendChild(option);
                });
            }
        });
    }

    function updateLogs() {
        currentPage = 1;
        currentFilters = {
            status: logStatus.value,
            endpoint: logEndpoint.value,
            date_from: logDateFrom.value,
            date_to: logDateTo.value,
            per_page: logsPerPage.value
        };
        loadLogs();
    }

    function loadLogs() {
        const params = new URLSearchParams({
            action: 'wpwps_get_logs',
            nonce: wpwps.nonce,
            page: currentPage,
            ...currentFilters
        });

        fetch(wpwps.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderLogs(data.logs);
                renderPagination(data.pages);
            }
        });
    }

    function renderLogs(data) {
        const tbody = document.getElementById('logsTableBody');
        tbody.innerHTML = '';

        data.items.forEach(log => {
            const tr = document.createElement('tr');
            tr.className = log.status === 'error' ? 'table-danger' : 
                          log.status === 'warning' ? 'table-warning' : '';
            
            tr.innerHTML = `
                <td>${formatDate(log.created_at)}</td>
                <td>${log.endpoint}</td>
                <td><span class="badge bg-${getBadgeClass(log.status)}">${log.status}</span></td>
                <td>${log.response_code || '-'}</td>
                <td>${log.message || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary view-details" data-log='${JSON.stringify(log)}'>
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });

        // Add event listeners to detail buttons
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const log = JSON.parse(this.dataset.log);
                showLogDetails(log);
            });
        });
    }

    function renderPagination(totalPages) {
        const pagination = document.getElementById('logsPagination');
        pagination.innerHTML = '';

        if (totalPages <= 1) return;

        // Previous button
        pagination.appendChild(createPageLink('‹', currentPage > 1 ? currentPage - 1 : null));

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (
                i === 1 || 
                i === totalPages || 
                (i >= currentPage - 2 && i <= currentPage + 2)
            ) {
                pagination.appendChild(createPageLink(i, i));
            } else if (
                i === currentPage - 3 || 
                i === currentPage + 3
            ) {
                pagination.appendChild(createPageLink('...', null));
            }
        }

        // Next button
        pagination.appendChild(createPageLink('›', currentPage < totalPages ? currentPage + 1 : null));
    }

    function createPageLink(text, page) {
        const li = document.createElement('li');
        li.className = `page-item${page === currentPage ? ' active' : ''}${page === null ? ' disabled' : ''}`;

        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = text;

        if (page !== null) {
            a.addEventListener('click', e => {
                e.preventDefault();
                currentPage = page;
                loadLogs();
            });
        }

        li.appendChild(a);
        return li;
    }

    function showLogDetails(log) {
        const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
        document.getElementById('logDetailsContent').textContent = 
            JSON.stringify(JSON.parse(log.context), null, 2);
        modal.show();
    }

    function formatDate(date) {
        return new Date(date).toLocaleString();
    }

    function getBadgeClass(status) {
        switch (status) {
            case 'error': return 'danger';
            case 'warning': return 'warning';
            case 'success': return 'success';
            default: return 'secondary';
        }
    }
}