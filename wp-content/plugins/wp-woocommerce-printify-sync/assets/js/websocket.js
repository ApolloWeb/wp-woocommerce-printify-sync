class PrintifyWebSocket {
    constructor() {
        this.socket = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.handlers = new Map();
        this.connected = false;

        this.init();
    }

    async init() {
        try {
            const auth = await this.getAuthToken();
            this.connect(auth.token);
        } catch (error) {
            console.error('WebSocket initialization failed:', error);
        }
    }

    async getAuthToken() {
        const response = await fetch(wpwpsAdmin.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'wpwps_get_ws_auth',
                nonce: wpwpsWebSocket.nonce,
            }),
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error('Failed to get WebSocket auth token');
        }

        return data.data;
    }

    connect(token) {
        const url = `${wpwpsWebSocket.socket_url}?token=${token}`;
        this.socket = new WebSocket(url);

        this.socket.onopen = () => {
            this.connected = true;
            this.reconnectAttempts = 0;
            this.trigger('connected');
        };

        this.socket.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.handleMessage(data);
            } catch (error) {
                console.error('WebSocket message parsing failed:', error);
            }
        };

        this.socket.onclose = () => {
            this.connected = false;
            this.trigger('disconnected');
            this.attemptReconnect();
        };

        this.socket.onerror = (error) => {
            console.error('WebSocket error:', error);
            this.trigger('error', error);
        };
    }

    attemptReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            this.trigger('reconnect_failed');
            return;
        }

        this.reconnectAttempts++;
        const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);

        setTimeout(() => {
            this.trigger('reconnecting', this.reconnectAttempts);
            this.init();
        }, delay);
    }

    on(event, handler) {
        if (!this.handlers.has(event)) {
            this.handlers.set(event, new Set());
        }
        this.handlers.get(event).add(handler);
    }

    off(event, handler) {
        if (this.handlers.has(event)) {
            this.handlers.get(event).delete(handler);
        }
    }

    trigger(event, data = null) {
        if (this.handlers.has(event)) {
            this.handlers.get(event).forEach(handler => handler(data));
        }
    }

    handleMessage(data) {
        switch (data.type) {
            case 'progress_update':
                this.trigger('progress_update', data.payload);
                break;
            case 'sync_status':
                this.trigger('sync_status', data.payload);
                break;
            case 'notification':
                this.trigger('notification', data.payload);
                break;
            default:
                this.trigger('message', data);
        }
    }

    send(type, payload) {
        if (!this.connected) {
            throw new Error('WebSocket is not connected');
        }

        this.socket.send(JSON.stringify({ type, payload }));
    }
}

// Initialize WebSocket connection
jQuery(document).ready(function($) {
    const socket = new PrintifyWebSocket();

    // Handle progress updates
    socket.on('progress_update', (data) => {
        $('.wpwps-progress-bar').each((_, element) => {
            const $element = $(element);
            const taskId = $element.data('task-id');
            
            if (data.taskId === taskId) {
                updateProgressBar($element, data);
            }
        });
    });

    // Handle sync status updates
    socket.on('sync_status', (data) => {
        const $statusBadge = $('.wpwps-sync-status');
        $statusBadge
            .removeClass('success error warning')
            .addClass(data.status)
            .text(data.message);
    });

    // Handle notifications
    socket.on('notification', (data) => {
        showNotification(data);
    });

    // Handle connection status
    socket.on('connected', () => {
        $('.wpwps-connection-status')
            .removeClass('disconnected')
            .addClass('connected')
            .text('Connected');
    });

    socket.on('disconnected', () => {
        $('.wpwps-connection-status')
            .removeClass('connected')
            .addClass('disconnected')
            .text('Disconnected');
    });

    socket.on('reconnecting', (attempt) => {
        $('.wpwps-connection-status')
            .text(`Reconnecting (Attempt ${attempt})...`);
    });

    function updateProgressBar($element, data) {
        const percentage = Math.round(data.percentage);
        const $progress = $element.find('.progress-bar');
        const $status = $element.find('.progress-status');
        const $percentage = $element.find('.progress-percentage');

        $progress.css('width', `${percentage}%`);
        $percentage.text(`${percentage}%`);
        $status.text(`${data.completed}/${data.total}`);

        if (data.status === 'completed') {
            $element.addClass('completed');
        } else if (data.status === 'failed') {
            $element.addClass('failed');
        }
    }

    function showNotification(data) {
        const $notification = $(`
            <div class="wpwps-notification ${data.type}" data-id="${data.id}">
                <div class="wpwps-notification-header">
                    <h3 class="wpwps-notification-title">${data.title}</h3>
                    ${data.dismissible ? '<a href="#" class="dismiss">×</a>' : ''}
                </div>
                <div class="wpwps-notification-content">
                    ${data.message}
                </div>
                ${data.actions ? createNotificationActions(data.actions) : ''}
            </div>
        `);

        $('.wpwps-notifications').prepend($notification);
        $notification.hide().slideDown(300);

        if (data.autoHide) {
            setTimeout(() => {
                $notification.slideUp(300, function() {
                    $(this).remove();
                });
            }, data.autoHide);
        }
    }

    function createNotificationActions(actions) {
        const actionButtons = actions.map(action => `
            <button class="button action-button ${action.class || ''}" 
                    data-action="${action.action}">
                ${action.text}
            </button>
        `).join('');

        return `<div class="wpwps-notification-actions">${actionButtons}</div>`;
    }
});