/**
 * Utility functions for the Printify Sync plugin.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Create WPWPS namespace
const WPWPS = (function($) {
    'use strict';
    
    /**
     * Toast notification system
     * 
     * @param {string} message - Message to display
     * @param {string} type - success, error, warning, or info
     * @param {number} duration - Duration in milliseconds
     */
    function showToast(message, type = 'info', duration = 5000) {
        let icon = 'fa-info-circle';
        let bgClass = 'bg-info';
        
        if (type === 'success') {
            icon = 'fa-check-circle';
            bgClass = 'bg-success';
        } else if (type === 'error') {
            icon = 'fa-exclamation-circle';
            bgClass = 'bg-danger';
        } else if (type === 'warning') {
            icon = 'fa-exclamation-triangle';
            bgClass = 'bg-warning';
        }
        
        const toast = $(`
            <div class="wpwps-toast ${bgClass}" style="display: none;">
                <div class="toast-icon">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="toast-content">
                    ${message}
                </div>
                <button type="button" class="toast-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `);
        
        // Add to container (create if doesn't exist)
        if ($('.wpwps-toast-container').length === 0) {
            $('body').append('<div class="wpwps-toast-container"></div>');
        }
        
        $('.wpwps-toast-container').append(toast);
        
        // Show with animation
        toast.fadeIn(300);
        
        // Auto-dismiss after duration
        setTimeout(() => {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, duration);
        
        // Close button
        toast.find('.toast-close').on('click', function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Format a number as currency
     * 
     * @param {number} amount - The amount to format
     * @param {string} currencySymbol - Currency symbol
     * @return {string} Formatted currency
     */
    function formatCurrency(amount, currencySymbol = '$') {
        return currencySymbol + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    
    /**
     * Format a date using a specified format
     * 
     * @param {string|Date} date - The date to format
     * @param {string} format - The format to use (simple)
     * @return {string} Formatted date
     */
    function formatDate(date, format = 'YYYY-MM-DD') {
        const d = new Date(date);
        
        if (isNaN(d.getTime())) {
            return 'Invalid date';
        }
        
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');
        
        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day)
            .replace('HH', hours)
            .replace('mm', minutes)
            .replace('ss', seconds);
    }
    
    /**
     * Get relative time (e.g. "5 hours ago")
     * 
     * @param {string|Date} date - The date to format
     * @return {string} Relative time
     */
    function getRelativeTime(date) {
        const d = new Date(date);
        const now = new Date();
        
        if (isNaN(d.getTime())) {
            return 'Invalid date';
        }
        
        const diffMs = now - d;
        const diffSec = Math.round(diffMs / 1000);
        const diffMin = Math.round(diffSec / 60);
        const diffHour = Math.round(diffMin / 60);
        const diffDay = Math.round(diffHour / 24);
        
        if (diffSec < 60) {
            return diffSec + ' seconds ago';
        } else if (diffMin < 60) {
            return diffMin + ' minutes ago';
        } else if (diffHour < 24) {
            return diffHour + ' hours ago';
        } else if (diffDay < 7) {
            return diffDay + ' days ago';
        } else {
            return formatDate(d, 'YYYY-MM-DD');
        }
    }
    
    /**
     * Toggle password visibility
     * 
     * @param {string} selector - The password input selector
     */
    function initPasswordToggles(selector = '.password-toggle') {
        $(selector).on('click', function() {
            const passwordInput = $(this).closest('.password-input-group').find('input');
            const icon = $(this).find('i');
            
            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordInput.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    }
    
    /**
     * Debounce function for performance
     * 
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @return {Function} Debounced function
     */
    function debounce(func, wait = 300) {
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
    
    /**
     * Show loading overlay
     * 
     * @param {string} message - Message to display
     * @return {object} Loading overlay object with show/hide methods
     */
    function showLoading(message = 'Loading...') {
        // Create overlay if it doesn't exist
        if ($('.wpwps-loading-overlay').length === 0) {
            const overlay = $(`
                <div class="wpwps-loading-overlay">
                    <div class="wpwps-loading-content">
                        <div class="wpwps-loading-spinner">
                            <i class="fas fa-circle-notch fa-spin"></i>
                        </div>
                        <p class="wpwps-loading-message">${message}</p>
                    </div>
                </div>
            `);
            
            $('body').append(overlay);
        } else {
            // Update message if overlay exists
            $('.wpwps-loading-message').text(message);
        }
        
        // Show overlay
        $('.wpwps-loading-overlay').addClass('active');
        
        // Return object with control methods
        return {
            hide: function() {
                $('.wpwps-loading-overlay').removeClass('active');
            },
            update: function(newMessage) {
                $('.wpwps-loading-message').text(newMessage);
            }
        };
    }
    
    /**
     * Create a modal dialog
     * 
     * @param {object} options - Modal options
     * @return {object} Modal object with show/hide methods
     */
    function createModal(options = {}) {
        const defaults = {
            title: 'Modal Dialog',
            content: '',
            size: 'medium', // small, medium, large
            buttons: [
                {
                    text: 'Close',
                    type: 'secondary',
                    click: function(modal) {
                        modal.hide();
                    }
                }
            ],
            onShow: null,
            onHide: null
        };
        
        const settings = $.extend({}, defaults, options);
        
        // Set modal width based on size
        let modalWidth = '600px';
        if (settings.size === 'small') {
            modalWidth = '400px';
        } else if (settings.size === 'large') {
            modalWidth = '800px';
        }
        
        // Create modal HTML
        const modalId = 'wpwps-modal-' + Math.floor(Math.random() * 1000000);
        const modal = $(`
            <div class="wpwps-modal-backdrop" id="${modalId}-backdrop">
                <div class="wpwps-modal" style="max-width: ${modalWidth}">
                    <div class="wpwps-modal-header">
                        <h3 class="wpwps-modal-title">${settings.title}</h3>
                        <button type="button" class="wpwps-modal-close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="wpwps-modal-body">${settings.content}</div>
                    <div class="wpwps-modal-footer">
                        <!-- Buttons will be added here -->
                    </div>
                </div>
            </div>
        `);
        
        // Add buttons
        const footer = modal.find('.wpwps-modal-footer');
        settings.buttons.forEach(function(button) {
            const btnClass = button.type === 'primary' ? 'btn-primary' : 'btn-secondary';
            const btn = $(`<button type="button" class="btn ${btnClass}">${button.text}</button>`);
            
            btn.on('click', function() {
                if (typeof button.click === 'function') {
                    button.click(modalObj);
                }
            });
            
            footer.append(btn);
        });
        
        // Modal object with methods
        const modalObj = {
            show: function() {
                // Append to body if not already
                if (!$('#' + modalId + '-backdrop').length) {
                    $('body').append(modal);
                }
                
                // Show modal with animation
                $('#' + modalId + '-backdrop').addClass('active');
                
                // Handle close button
                $('#' + modalId + '-backdrop .wpwps-modal-close').on('click', function() {
                    modalObj.hide();
                });
                
                // Close on backdrop click
                $('#' + modalId + '-backdrop').on('click', function(e) {
                    if ($(e.target).is('#' + modalId + '-backdrop')) {
                        modalObj.hide();
                    }
                });
                
                // Run onShow callback
                if (typeof settings.onShow === 'function') {
                    settings.onShow(modalObj);
                }
                
                // Handle ESC key
                $(document).on('keydown.wpwpsModal', function(e) {
                    if (e.keyCode === 27) { // ESC key
                        modalObj.hide();
                    }
                });
            },
            hide: function() {
                $('#' + modalId + '-backdrop').removeClass('active');
                
                // Run onHide callback
                if (typeof settings.onHide === 'function') {
                    settings.onHide(modalObj);
                }
                
                // Remove ESC key handler
                $(document).off('keydown.wpwpsModal');
                
                // Remove from DOM after animation
                setTimeout(function() {
                    $('#' + modalId + '-backdrop').remove();
                }, 300);
            },
            getElement: function() {
                return $('#' + modalId + '-backdrop');
            },
            setTitle: function(title) {
                $('#' + modalId + '-backdrop .wpwps-modal-title').text(title);
            },
            setContent: function(content) {
                $('#' + modalId + '-backdrop .wpwps-modal-body').html(content);
            }
        };
        
        return modalObj;
    }
    
    /**
     * Initialize the plugin's UI components
     */
    function initUI() {
        // Add toast container styles
        $('head').append(`
            <style>
                .wpwps-toast-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }
                
                .wpwps-toast {
                    background-color: white;
                    color: white;
                    border-radius: 10px;
                    padding: 12px 15px;
                    min-width: 300px;
                    max-width: 400px;
                    display: flex;
                    align-items: center;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    font-family: 'Inter', sans-serif;
                }
                
                .toast-icon {
                    margin-right: 12px;
                    font-size: 1.25rem;
                }
                
                .toast-content {
                    flex: 1;
                    font-size: 0.9rem;
                }
                
                .toast-close {
                    background: none;
                    border: none;
                    color: white;
                    opacity: 0.7;
                    cursor: pointer;
                    transition: opacity 0.2s ease;
                }
                
                .toast-close:hover {
                    opacity: 1;
                }
                
                .wpwps-loading-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                
                .wpwps-loading-overlay.active {
                    opacity: 1;
                }
                
                .wpwps-loading-content {
                    text-align: center;
                    color: white;
                }
                
                .wpwps-loading-spinner {
                    font-size: 2rem;
                    margin-bottom: 10px;
                }
                
                .wpwps-modal-backdrop {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                
                .wpwps-modal-backdrop.active {
                    opacity: 1;
                }
                
                .wpwps-modal {
                    background: white;
                    border-radius: 10px;
                    padding: 20px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
                    max-width: 600px;
                    width: 100%;
                    position: relative;
                }
                
                .wpwps-modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }
                
                .wpwps-modal-title {
                    font-size: 1.5rem;
                    margin: 0;
                }
                
                .wpwps-modal-close {
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                }
                
                .wpwps-modal-body {
                    margin-bottom: 20px;
                }
                
                .wpwps-modal-footer {
                    text-align: right;
                }
                
                .btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                }
                
                .btn-primary {
                    background: #007bff;
                    color: white;
                }
                
                .btn-secondary {
                    background: #6c757d;
                    color: white;
                }
                
                .btn-primary:hover {
                    background: #0056b3;
                }
                
                .btn-secondary:hover {
                    background: #5a6268;
                }
            </style>
        `);
        
        // Initialize all password toggles
        initPasswordToggles();
        
        // Add animation classes to cards
        $('.wpwps-card').addClass('wpwps-fade-in');
        
        // Initialize popovers if Bootstrap is available
        if (typeof $.fn.popover !== 'undefined') {
            $('[data-bs-toggle="popover"]').popover();
        }
        
        // Initialize tooltips if Bootstrap is available
        if (typeof $.fn.tooltip !== 'undefined') {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    }
    
    // Public API
    return {
        showToast: showToast,
        formatCurrency: formatCurrency,
        formatDate: formatDate,
        getRelativeTime: getRelativeTime,
        debounce: debounce,
        initUI: initUI,
        showLoading: showLoading,
        createModal: createModal
    };
    
})(jQuery);

// Initialize when document is ready
jQuery(document).ready(function($) {
    WPWPS.initUI();
});
