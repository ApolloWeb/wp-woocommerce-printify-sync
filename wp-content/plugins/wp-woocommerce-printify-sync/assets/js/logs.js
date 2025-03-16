class LogViewer {
    constructor() {
        this.filters = {
            search: '',
            level: '',
            date_from: '',
            date_to: '',
            page: 1,
            per_page: 25
        };
        
        this.bindEvents();
        this.loadLogs();
    }

    bindEvents() {
        document.getElementById('searchLogs').addEventListener('input', this.debounce(() => {
            this.filters.search = event.target.value;
            this.filters.page = 1;
            this.loadLogs();
        }, 500));

        document.getElementById('logLevel').addEventListener('change', () => {
            this.filters.level = event.target.value;
            this.filters.page = 1;
            this.loadLogs();
        });

        document.getElementById('dateFrom').addEventListener('change', () => {
            this.filters.date_from = event.target.value;
            this.filters.page = 1;
            this.loadLogs();
        });

        document.getElementById('dateTo').addEventListener('change', () => {
            this.filters.date_to = event.target.value;
            this.filters.page = 1;
            this.loadLogs();
        });

        document.getElementById('refreshLogs').addEventListener('click', () => {
            this.loadLogs();
        });

        document.getElementById('exportLogs').addEventListener('click', () => {
            this.exportLogs();
        });
    }

    async loadLogs() {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_printify_logs',
                    nonce: printifySync.nonce,
                    ...this.filters
                })
            });

            const data = await response.json();
            if (data.success) {
                this.renderLogs(data.data.logs);
                this.renderPagination(data.data.pagination);
            }
        } catch (error) {
            console.error('Error loading logs:', error);
        }
    }

    renderLogs(logs) {
        const tbody = document.querySelector('#logsTable tbody');
        tbody.innerHTML = '';

        logs.forEach(log => {
            const tr = document.createElement('tr');
            tr.className = `log-level-${log.level}`;
            tr.innerHTML = `
                <td>${this.formatDate(log.created_at)}</td>
                <td><span class="badge bg-${this.getLevelColor(log.level)}">${log.level}</span></td>
                <td>${this.truncate(log.message, 100)}</td>
                <td>${this.formatContext(log.context)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary view-log" data-log-id="${log.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${log.cloud_url ? `
                        <a href="${log.cloud_url}" class="btn btn-sm btn-outline-secondary" target="_blank">
                            <i class="fas fa-cloud-download-alt"></i>
                        </a>
                    ` : ''}
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Bind view log events
        document.querySelectorAll('.view-log').forEach(btn => {
            btn.addEventListener('click', () => this.viewLogDetail(btn.dataset.logId));
        });
    }

    getLevelColor(level) {
        return {
            error: 'danger',
            warning: 'warning',
            info: 'info',
            debug: 'secondary'
        }[level] || 'secondary';
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ... Additional methods for pagination, export, etc.
}

// Initialize on document load
document.addEventListener('DOMContentLoaded', () => {
    new LogViewer();
});