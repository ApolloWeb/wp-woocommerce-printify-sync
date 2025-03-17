jQuery(document).ready(function($) {
    // Refresh stats periodically
    function refreshStats() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_get_webhook_stats',
                nonce: wpwps.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                }
            }
        });
    }

    function updateDashboard(data) {
        // Update health status
        $('.wpwps-health-status')
            .removeClass('healthy warning error')
            .addClass(data.health.status);
        $('.status-message').text(data.health.message);

        // Update stats
        Object.keys(data.stats).forEach(key => {
            $(`.stat-value[data-stat="${key}"]`).text(data.stats[key]);
        });

        // Update events table
        updateEventsTable(data.events);
    }

    function updateEventsTable(events) {
        const tbody = $('.wpwps-recent-events tbody');
        tbody.empty();

        events.forEach(event => {
            tbody.append(`
                <tr>
                    <td>${event.time_ago}</td>
                    <td>${event.topic}</td>
                    <td>
                        <span class="wpwps-status-badge ${event.status}">
                            ${event.status}
                        </span>
                    </td>
                    <td>${event.response_time}ms</td>
                    <td>
                        <button class="button-link view-details" 
                                data-event-id="${event.id}">
                            View Details
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // Event details modal
    $('.view-details').on('click', function() {
        const eventId = $(this).data('event-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_get_event_details',
                event_id: eventId,
                nonce: wpwps.nonce
            },
            success: function(response) {
                if (response.success) {
                    showEventDetails(response.data);
                }
            }
        });
    });

    function showEventDetails(data) {
        const modal = $('#wpwps-event-details');
        const content = modal.find('.event-details-content');

        content.html(`
            <div class="event-detail">
                <strong>Topic:</strong> ${data.topic}
            </div>
            <div class="event-detail">
                <strong>Timestamp:</strong> ${data.timestamp}
            </div>
            <div class="event-detail">
                <strong>Status:</strong> 
                <span class="wpwps-status-badge ${data.status}">
                    ${data.status}
                </span>
            </div>
            <div class="event-detail">
                <strong>Response Time:</strong> ${data.response_time}ms
            </div>
            <div class="event-detail">
                <strong>Payload:</strong>
                <pre>${JSON.stringify(data.payload, null, 2)}</pre>
            </div>
            ${data.error ? `
                <div class="event-detail error">
                    <strong>Error:</strong>
                    <pre>${data.error}</pre>
                </div>
            ` : ''}
        `);

        modal.show();
    }

    // Close modal
    $('.wpwps-modal .close').on('click', function() {
        $(this).closest('.wpwps-modal').hide();
    });

    // Auto-refresh
    setInterval(refreshStats, 30000); // Every 30 seconds
});