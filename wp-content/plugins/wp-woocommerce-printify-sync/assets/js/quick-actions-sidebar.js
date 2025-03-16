class QuickActionsSidebar {
    constructor() {
        this.sidebar = document.getElementById('quickActionsSidebar');
        this.bindEvents();
        this.initializeWebSocket();
        this.startStatusUpdates();
    }

    bindEvents() {
        // Toggle sidebar
        document.getElementById('toggleSidebar')?.addEventListener('click', () => {
            this.toggleSidebar();
        });

        // Close sidebar
        document.getElementById('closeSidebar')?.addEventListener('click', () => {
            this.closeSidebar();
        });

        // Quick task buttons
        document.querySelectorAll('.task-button').forEach(button => {
            button.addEventListener('click', (e) => {
                this.handleQuickTask(e.currentTarget.dataset.action);
            });
        });

        // Keyboard shortcut
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'q') {
                e.preventDefault();
                this.toggleSidebar();
            }
        });
    }

    toggleSidebar() {
        this.sidebar.classList.toggle('active');
    }

    closeSidebar() {
        this.sidebar.classList.remove('active');
    }

    async handleQuickTask(action) {
        try {
            const response = await this.makeRequest(action);
            
            if (response.success) {
                this.showNotification('success', response.message);
                this.updateActivity(action, 'success');
            } else {
                this.showNotification('error', response.message);
                this.updateActivity(action, 'error');
            }
        } catch (error) {
            this.showNotification('error', 'Task failed. Please try again.');
            this.updateActivity(action, 'error');
        }
    }

    initializeWebSocket() {
        // Initialize WebSocket connection for real-time updates
        this.ws = new WebSocket(printifySync.wsUrl);

        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleWebSocketMessage(data);
        };

        this.ws.onclose = () => {
            // Attempt to reconnect after 5 seconds
            setTimeout(() => this.initializeWebSocket(), 5000);
        };
    }

    handleWebSocketMessage(data) {
        switch (data.type) {
            case 'status_update':
                this.updateStatus(data.status);
                break;
            case 'activity':
                this.updateActivity(data.activity);
                break;
            case 'notification':
                this.showNotification(data.level, data.message);
                break;
        }
    }

    updateStatus(status) {
        // Update status indicators
        document.getElementById('apiStatus').innerHTML = `
            <i class="fas fa-circle text-${status.api ? 'success' : 'danger'}"></i>
            ${status.api ? 'Connected' : 'Disconnected'}
        `;

        document.getElementById('lastSync').textContent = status.lastSync;
        
        document.getElementById('queueStatus').innerHTML = `
            <i class="fas fa-circle text-${status.queue.status === 'processing' ? 'success' : 'warning'}"></i>
            ${status.queue.status}
        `;
    }

    updateActivity(activity) {
        const activityList = document.getElementById('recentActivity');
        const activityItem = document.createElement('div');
        activityItem.className = 'activity-item';
        activityItem.innerHTML = `
            <i class="fas fa-${this.getActivityIcon(activity.type)} text-${activity.status}"></i>
            <span>${activity.message}</span>
            <small>${activity.time}</small>
        `;

        activityList.insertBefore(activityItem, activityList.firstChild);

        // Limit the number of activity items
        if (activityList.children.length > 10) {
            activityList.lastChild.remove();
        }
    }

    getActivityIcon(type) {
        const icons = {
            sync: 'sync',
            stock: 'boxes',
            cache: 'broom',
            log: 'list',
            error: 'exclamation-circle',
            success: 'check-circle'
        };
        return icons[type] || 'info-circle';
    }

    startStatusUpdates() {
        // Poll for status updates every 30 seconds
        setInterval(() => this.pollStatus(), 30000);
    }

    async pollStatus() {
        try {
            const response = await this.makeRequest('get_system_status');
            if (response.success) {
                this.updateStatus(response.status);
            }
        } catch (error) {
            console.error('Failed to poll status:', error);
        }
    }

    async makeRequest(action, data = {}) {
        const response = await fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: action,
                nonce: printifySync.nonce,
                ...data
            })
        });

        return await response.json();
    }
}

// Initialize on document load
document.addEventListener('DOMContentLoaded', () => {
    new QuickActionsSidebar();
});