/**
 * Custom Bootstrap UI behaviors for WP WooCommerce Printify Sync
 */
(function($) {
    'use strict';

    const WPWPSUI = {
        init: function() {
            this.initSidebar();
            this.initToasts();
            this.initNotifications();
            this.initSearch();
        },

        initSidebar: function() {
            // Set initial sidebar state
            if (wpwpsUI.sidebar_state === 'collapsed') {
                $('.wpwps-sidebar').addClass('collapsed');
                $('.wpwps-main-content').addClass('expanded');
            }

            // Handle sidebar toggle
            $('.sidebar-toggle').on('click', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_toggle_sidebar',
                        nonce: wpwps.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.wpwps-sidebar').toggleClass('collapsed');
                            $('.wpwps-main-content').toggleClass('expanded');
                        }
                    }
                });
            });
        },

        initToasts: function() {
            // Create toast container if not exists
            if ($('.toast-container').length === 0) {
                $('body').append('<div class="toast-container"></div>');
            }
            
            // Initialize existing toasts
            $('.toast').toast();
            
            // Method to show a new toast notification
            window.showToast = function(title, message, type = 'info', autohide = true, delay = 5000) {
                const icon = type === 'success' ? 'check-circle' : 
                            type === 'danger' ? 'exclamation-circle' :
                            type === 'warning' ? 'exclamation-triangle' : 'info-circle';
                
                const toast = `
                    <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="${autohide}" data-bs-delay="${delay}">
                        <div class="toast-header">
                            <i class="fas fa-${icon} me-2 text-${type}"></i>
                            <strong class="me-auto">${title}</strong>
                            <small>Just now</small>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            ${message}
                        </div>
                    </div>
                `;
                
                $('.toast-container').append(toast);
                $('.toast:last').toast('show');
                
                // Remove the toast from DOM after it's hidden
                $('.toast:last').on('hidden.bs.toast', function() {
                    $(this).remove();
                });
            };
        },

        initNotifications: function() {
            // Populate notifications dropdown
            if (wpwpsUI.notifications && wpwpsUI.notifications.length) {
                const $notificationsList = $('.notifications-menu');
                $notificationsList.empty();
                
                wpwpsUI.notifications.forEach(function(notification) {
                    const notificationItem = `
                        <a href="#" class="dropdown-item wpwps-notification-item ${!notification.read ? 'unread' : ''}" data-id="${notification.id}">
                            <div class="d-flex align-items-center">
                                <div class="wpwps-notification-icon">
                                    <i class="${notification.icon} ${notification.icon_color}"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">${notification.title}</h6>
                                    <p class="mb-1 small text-muted">${notification.message}</p>
                                    <small class="text-muted">${notification.time}</small>
                                </div>
                            </div>
                        </a>
                    `;
                    
                    $notificationsList.append(notificationItem);
                });
                
                // Update notification badge
                const unreadCount = wpwpsUI.notifications.filter(n => !n.read).length;
                if (unreadCount > 0) {
                    $('.notifications-badge').text(unreadCount).show();
                } else {
                    $('.notifications-badge').hide();
                }
            }
            
            // Handle notification click (mark as read)
            $(document).on('click', '.wpwps-notification-item', function(e) {
                e.preventDefault();
                
                const $notification = $(this);
                const notificationId = $notification.data('id');
                
                $.ajax({
                    url: wpwps.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpwps_dismiss_notification',
                        nonce: wpwps.nonce,
                        id: notificationId
                    },
                    success: function(response) {
                        if (response.success) {
                            $notification.removeClass('unread');
                            
                            // Update badge count
                            const currentCount = parseInt($('.notifications-badge').text());
                            if (currentCount > 1) {
                                $('.notifications-badge').text(currentCount - 1);
                            } else {
                                $('.notifications-badge').hide();
                            }
                        }
                    }
                });
            });
        },

        initSearch: function() {
            $('.navbar-search').on('submit', function(e) {
                e.preventDefault();
                
                const searchQuery = $(this).find('input[name="search"]').val();
                if (!searchQuery.trim()) return;
                
                // Here you would typically redirect to search results
                // For now, just show a toast
                showToast('Search', `Searching for: ${searchQuery}`, 'info');
            });
        }
    };

    $(document).ready(function() {
        WPWPSUI.init();
    });

})(jQuery);
