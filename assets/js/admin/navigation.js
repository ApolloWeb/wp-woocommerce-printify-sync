/**
 * WooCommerce Printify Sync - Navigation JavaScript
 * 
 * Handles responsive navigation and hamburger menu
 */

(function($) {
    'use strict';

    var PrintifyNavigation = {
        
        init: function() {
            this.setupHamburgerMenu();
            this.setupResponsiveNavigation();
            this.highlightCurrentPage();
            this.setupSubMenus();
        },
        
        // Set up hamburger menu for mobile
        setupHamburgerMenu: function() {
            // Create hamburger button if it doesn't exist
            if ($('.printify-hamburger-menu').length === 0 && $('.printify-main-nav').length > 0) {
                $('.printify-header').append(`
                    <button class="printify-hamburger-menu" aria-label="Menu">
                        <span class="hamburger-box">
                            <span class="hamburger-inner"></span>
                        </span>
                    </button>
                `);
            }
            
            // Toggle menu when clicking hamburger
            $('.printify-hamburger-menu').on('click', function() {
                $(this).toggleClass('is-active');
                $('.printify-main-nav').toggleClass('is-active');
                $('body').toggleClass('nav-open');
            });
            
            // Close menu when clicking outside
            $(document).on('click', function(event) {
                if (!$(event.target).closest('.printify-hamburger-menu, .printify-main-nav').length) {
                    $('.printify-hamburger-menu').removeClass('is-active');
                    $('.printify-main-nav').removeClass('is-active');
                    $('body').removeClass('nav-open');
                }
            });
        },
        
        // Handle responsive behavior
        setupResponsiveNavigation: function() {
            // Check window size and adjust navigation
            function checkWindowSize() {
                if (window.innerWidth <= 768) {
                    $('.printify-main-nav').addClass('mobile');
                } else {
                    $('.printify-main-nav').removeClass('mobile');
                    $('.printify-hamburger-menu').removeClass('is-active');
                    $('.printify-main-nav').removeClass('is-active');
                    $('body').removeClass('nav-open');
                }
            }
            
            // Initial check
            checkWindowSize();
            
            // Check on window resize
            $(window).on('resize', function() {
                checkWindowSize();
            });
        },
        
        // Highlight current page in navigation
        highlightCurrentPage: function() {
            // Get current page from URL
            var currentPage = window.location.href;
            
            // Find matching nav item
            $('.printify-main-nav a').each(function() {
                var linkHref = $(this).attr('href');
                if (currentPage.indexOf(linkHref) > -1) {
                    $(this).addClass('active');
                    $(this).closest('.has-submenu').addClass('active');
                }
            });
        },
        
        // Setup submenu toggles
        setupSubMenus: function() {
            $('.printify-main-nav .has-submenu > a').on('click', function(e) {
                // Only prevent default if in mobile view
                if ($('.printify-main-nav').hasClass('mobile')) {
                    e.preventDefault();
                    $(this).parent().toggleClass('submenu-open');
                    $(this).next('.submenu').slideToggle(200);
                }
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        PrintifyNavigation.init();
    });
    
})(jQuery);