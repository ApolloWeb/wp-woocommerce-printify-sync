/**
 * Enhanced UI interactions for WP WooCommerce Printify Sync
 *
 * Implements modern user experience design patterns following SOLID principles
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initEnhancedDataVisualizations();
        initAnimatedElements();
        initEnhancedCards();
        initInteractiveCharts();
        initResponsiveLayouts();
    });
    
    /**
     * Initialize enhanced data visualizations
     */
    function initEnhancedDataVisualizations() {
        // Animate progress bars when they come into view
        animateElementsOnView('.progress-wpwps .progress-bar', function(el) {
            const targetWidth = $(el).data('width') || '0';
            $(el).css('width', targetWidth + '%');
        });
        
        // Animate value indicators
        animateElementsOnView('.value-indicator .indicator-bar:after', function(el) {
            const value = $(el).parent().data('value') || 0;
            $(el).css('width', value + '%');
        });
        
        // Initialize count-up animations for statistics
        $('.stats-number').each(function() {
            const $this = $(this);
            const countTo = parseInt($this.text().replace(/,/g, ''), 10);
            
            $this.text('0');
            
            animateElementsOnView($this, function(el) {
                $(el).prop('Counter', 0).animate({
                    Counter: countTo
                }, {
                    duration: 1500,
                    easing: 'swing',
                    step: function(now) {
                        $(el).text(Math.ceil(now).toLocaleString());
                    }
                });
            });
        });
    }
    
    /**
     * Initialize animated elements
     */
    function initAnimatedElements() {
        // Stagger animations for lists of items
        $('.stagger-animate').each(function() {
            const $items = $(this).children();
            
            $items.each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
            });
        });
        
        // Add entrance animations to elements
        animateElementsOnView('.animate-on-view', function(el) {
            $(el).addClass('animated');
        });
        
        // Shimmer effect for loading placeholders
        setInterval(function() {
            $('.shimmer').css('background-position', 'right');
            
            setTimeout(function() {
                $('.shimmer').css('background-position', 'left');
            }, 1500);
        }, 3000);
    }
    
    /**
     * Initialize enhanced cards with interaction effects
     */
    function initEnhancedCards() {
        // Add parallax tilt effect to featured cards
        $('.card-featured').on('mousemove', function(e) {
            const card = $(this);
            const cardRect = card[0].getBoundingClientRect();
            const cardCenterX = cardRect.left + cardRect.width / 2;
            const cardCenterY = cardRect.top + cardRect.height / 2;
            
            const mouseX = e.clientX;
            const mouseY = e.clientY;
            
            // Calculate tilt amount (max 15 degrees)
            const tiltX = ((mouseY - cardCenterY) / (cardRect.height / 2)) * -8;
            const tiltY = ((mouseX - cardCenterX) / (cardRect.width / 2)) * 8;
            
            // Apply transform
            card.css('transform', `perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale3d(1.02, 1.02, 1.02)`);
        }).on('mouseleave', function() {
            // Reset transform
            $(this).css('transform', 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)');
        });
        
        // Add hover states for action cards
        $('.card-action').hover(
            function() {
                const $icon = $(this).find('.stats-icon');
                $icon.addClass('pulse-animation');
            },
            function() {
                const $icon = $(this).find('.stats-icon');
                $icon.removeClass('pulse-animation');
            }
        );
    }
    
    /**
     * Initialize interactive charts with enhanced animations
     */
    function initInteractiveCharts() {
        // If Chart.js exists, enhance the default configuration
        if (typeof Chart !== 'undefined') {
            Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
            Chart.defaults.color = '#6c757d';
            Chart.defaults.elements.line.tension = 0.4;
            Chart.defaults.plugins.tooltip.titleFont.weight = '600';
            Chart.defaults.plugins.tooltip.bodyFont.size = 13;
            Chart.defaults.plugins.legend.labels.padding = 10;
            
            // Custom animation for chart elements
            Chart.defaults.animation = {
                duration: 2000,
                easing: 'easeOutQuart'
            };
            
            // Create gradient backgrounds for charts
            $('.chart-container').each(function() {
                const canvas = $(this).find('canvas').get(0);
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    
                    // Store gradients as data attributes
                    if (ctx) {
                        // Primary gradient
                        const primaryGradient = ctx.createLinearGradient(0, 0, 0, 400);
                        primaryGradient.addColorStop(0, 'rgba(150, 88, 138, 0.6)');
                        primaryGradient.addColorStop(1, 'rgba(150, 88, 138, 0.1)');
                        $(canvas).data('primaryGradient', primaryGradient);
                        
                        // Secondary gradient
                        const secondaryGradient = ctx.createLinearGradient(0, 0, 0, 400);
                        secondaryGradient.addColorStop(0, 'rgba(0, 119, 182, 0.6)');
                        secondaryGradient.addColorStop(1, 'rgba(0, 119, 182, 0.1)');
                        $(canvas).data('secondaryGradient', secondaryGradient);
                    }
                }
            });
        }
    }
    
    /**
     * Initialize responsive layouts with enhanced behavior
     */
    function initResponsiveLayouts() {
        // Adjust layout based on screen size
        function handleResponsiveLayout() {
            const width = $(window).width();
            
            // Mobile optimizations
            if (width < 768) {
                $('.data-cards-row').removeClass('row-cols-md-3').addClass('row-cols-1');
                $('.stats-card h2').addClass('fs-4'); // Smaller heading on mobile
            } else {
                $('.data-cards-row').removeClass('row-cols-1').addClass('row-cols-md-3');
                $('.stats-card h2').removeClass('fs-4');
            }
            
            // Tablet optimizations
            if (width >= 768 && width < 992) {
                $('.data-cards-row').removeClass('row-cols-md-3').addClass('row-cols-md-2');
            }
        }
        
        // Run once on init
        handleResponsiveLayout();
        
        // Run on window resize
        $(window).on('resize', function() {
            handleResponsiveLayout();
        });
        
        // Enhance scroll behavior for smooth navigation
        $('.smooth-scroll').on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            $('html, body').animate({
                scrollTop: $(target).offset().top - 70
            }, 800, 'easeOutCubic');
        });
    }
    
    /**
     * Helper function to animate elements when they come into view
     * 
     * @param {string} selector CSS selector for target elements
     * @param {function} callback Function to call when element enters viewport
     */
    function animateElementsOnView(selector, callback) {
        const elements = $(selector).toArray();
        
        // Initial check for elements already in view
        checkElementsInView();
        
        // Check on scroll
        $(window).on('scroll', function() {
            checkElementsInView();
        });
        
        function checkElementsInView() {
            const viewportHeight = $(window).height();
            const scrollTop = $(window).scrollTop();
            
            elements.forEach(function(element, index) {
                if (!element.animated) {
                    const elementTop = $(element).offset().top;
                    
                    // If element is in viewport
                    if (elementTop < (scrollTop + viewportHeight - 50)) {
                        element.animated = true;
                        callback(element, index);
                    }
                }
            });
        }
    }
    
    // Expose common functions to global scope if needed
    window.wpwpsUI = {
        animateElement: function(selector) {
            $(selector).addClass('animated');
        },
        refreshVisualizations: function() {
            initEnhancedDataVisualizations();
        }
    };
    
})(jQuery);
