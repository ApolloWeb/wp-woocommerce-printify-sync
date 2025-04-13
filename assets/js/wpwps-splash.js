/**
 * Premium Splash Screen for WP WooCommerce Printify Sync
 */
(function($) {
    'use strict';
    
    // Create and display the splash screen
    $(document).ready(function() {
        // Only show on plugin pages
        if ($('body').hasClass('wpwps-premium-ui')) {
            createSplashScreen();
            
            // Hide splash screen after content is loaded
            $(window).on('load', function() {
                hideSplashScreen();
            });
            
            // Failsafe: Hide splash after 3 seconds even if not fully loaded
            setTimeout(hideSplashScreen, 3000);
        }
    });
    
    /**
     * Create the splash screen
     */
    function createSplashScreen() {
        // Create loader HTML
        const loaderHTML = `
            <div class="wpwps-page-loader">
                <div class="wpwps-loader-logo">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M40 0C17.909 0 0 17.909 0 40C0 62.091 17.909 80 40 80C62.091 80 80 62.091 80 40C80 17.909 62.091 0 40 0ZM40 12C54.359 12 66 23.641 66 38C66 52.359 54.359 64 40 64C25.641 64 14 52.359 14 38C14 23.641 25.641 12 40 12Z" fill="url(#gradient-primary)"/>
                        <path d="M40 22C31.163 22 24 29.163 24 38C24 46.837 31.163 54 40 54C48.837 54 56 46.837 56 38C56 29.163 48.837 22 40 22ZM40 42C37.794 42 36 40.206 36 38C36 35.794 37.794 34 40 34C42.206 34 44 35.794 44 38C44 40.206 42.206 42 40 42Z" fill="url(#gradient-secondary)"/>
                        <defs>
                            <linearGradient id="gradient-primary" x1="0" y1="0" x2="80" y2="80" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#96588a"/>
                                <stop offset="1" stop-color="#7d4675"/>
                            </linearGradient>
                            <linearGradient id="gradient-secondary" x1="24" y1="22" x2="56" y2="54" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#3a7bd5"/>
                                <stop offset="1" stop-color="#2a63b9"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div class="wpwps-loader-spinner">
                    <div class="wpwps-loader-circle wpwps-loader-circle-1"></div>
                    <div class="wpwps-loader-circle wpwps-loader-circle-2"></div>
                    <div class="wpwps-loader-circle wpwps-loader-circle-3"></div>
                </div>
                <div class="wpwps-loader-text">
                    Loading Printify Sync
                    <span class="wpwps-loader-dots">
                        <span class="wpwps-loader-dot"></span>
                        <span class="wpwps-loader-dot"></span>
                        <span class="wpwps-loader-dot"></span>
                    </span>
                </div>
            </div>
        `;
        
        // Add loader to the page
        $('body').prepend(loaderHTML);
    }
    
    /**
     * Hide the splash screen with animation
     */
    function hideSplashScreen() {
        const loader = $('.wpwps-page-loader');
        if (loader.length) {
            loader.addClass('loaded');
            
            // Remove from DOM after animation completes
            setTimeout(function() {
                loader.remove();
            }, 700);
        }
    }
})(jQuery);
