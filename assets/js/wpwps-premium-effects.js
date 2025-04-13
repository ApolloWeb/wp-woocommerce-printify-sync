/**
 * Premium Visual Effects for WP WooCommerce Printify Sync
 *
 * Implements breathtaking animations, interactions and visual effects
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initBackgroundEffects();
        init3DCards();
        initParticleEffects();
        initPremiumAnimations();
        initParallaxEffects();
        initScrollEffects();
        initLightTrails();
        initPremiumCharts();
        initHoverEffects();
        initPremiumTabs();
        initPageTransitions();
    });
    
    /**
     * Initialize background shape animations
     */
    function initBackgroundEffects() {
        const body = $('body');
        
        // Add background shapes container if it doesn't exist
        if (!$('.wpwps-bg-shapes').length) {
            body.prepend('<div class="wpwps-bg-shapes"></div>');
        }
        
        // Create floating particles for background
        const bgShapesCount = 5;
        const shapes = $('.wpwps-bg-shapes');
        
        for (let i = 0; i < bgShapesCount; i++) {
            const size = Math.random() * 300 + 100; // Random size between 100-400px
            const positionX = Math.random() * 100;
            const positionY = Math.random() * 100;
            const rotate = Math.random() * 360;
            const opacity = Math.random() * 0.08 + 0.02;
            const animDuration = Math.random() * 20 + 20; // 20-40s
            const animDelay = Math.random() * -20;
            const color = Math.random() > 0.5 ? 'var(--wpwps-primary)' : 'var(--wpwps-secondary)';
            
            const shape = $('<div class="wpwps-bg-shape"></div>').css({
                position: 'absolute',
                width: size + 'px',
                height: size + 'px',
                borderRadius: '50%',
                background: 'radial-gradient(circle, ' + color + ' 0%, transparent 70%)',
                left: positionX + 'vw',
                top: positionY + 'vh',
                opacity: opacity,
                transform: 'rotate(' + rotate + 'deg)',
                animation: 'float ' + animDuration + 's ease-in-out infinite alternate ' + animDelay + 's',
                zIndex: -1
            });
            
            shapes.append(shape);
        }
    }
    
    /**
     * Initialize 3D card effects
     */
    function init3DCards() {
        // Apply 3D effect to premium cards
        $('.card-premium').addClass('card-3d').wrapInner('<div class="card-3d-inner"></div>');
        
        // Create 3D tilt effect on mouse move
        $('.card-3d').on('mousemove', function(e) {
            const card = $(this);
            const cardRect = card[0].getBoundingClientRect();
            
            // Calculate card center position
            const cardCenterX = cardRect.left + cardRect.width / 2;
            const cardCenterY = cardRect.top + cardRect.height / 2;
            
            // Calculate mouse position relative to card center
            const mouseX = e.clientX;
            const mouseY = e.clientY;
            
            // Calculate the tilt effect (maximum 15 degrees)
            const tiltY = ((mouseX - cardCenterX) / (cardRect.width / 2)) * 8;
            const tiltX = -1 * ((mouseY - cardCenterY) / (cardRect.height / 2)) * 8;
            
            // Apply the tilt effect with a smooth transition
            card.find('.card-3d-inner').css({
                'transform': `perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale3d(1.02, 1.02, 1.02)`,
                'transition': 'transform 0.05s linear'
            });
            
            // Light effect that follows mouse
            const glareX = ((mouseX - cardCenterX) / cardRect.width) * 100 + 50;
            const glareY = ((mouseY - cardCenterY) / cardRect.height) * 100 + 50;
            
            card.find('.card-3d-inner').css({
                'background': `radial-gradient(circle at ${glareX}% ${glareY}%, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 50%)`
            });
        }).on('mouseleave', function() {
            // Reset the transform when mouse leaves
            $(this).find('.card-3d-inner').css({
                'transform': 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)',
                'transition': 'transform 0.5s ease-out',
                'background': 'none'
            });
        });
    }
    
    /**
     * Initialize particle effects for certain elements
     */
    function initParticleEffects() {
        // Create SVG elements for loaders
        $('.loader-spectacular').each(function() {
            const loader = $(this);
            
            // Create SVG element with circular path
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('viewBox', '0 0 50 50');
            
            // Create circle path
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('class', 'loader-path');
            circle.setAttribute('cx', '25');
            circle.setAttribute('cy', '25');
            circle.setAttribute('r', '20');
            svg.appendChild(circle);
            
            // Add SVG to loader element
            loader.append(svg);
        });
        
        // Add glowing particles to the dashboard
        if ($('.dashboard-header-premium').length) {
            const particleCount = 15;
            const headerElement = $('.dashboard-header-premium');
            
            for (let i = 0; i < particleCount; i++) {
                const size = Math.random() * 6 + 4; // 4-10px
                const particle = $('<div class="particle"></div>').css({
                    position: 'absolute',
                    width: size + 'px',
                    height: size + 'px',
                    borderRadius: '50%',
                    background: i % 2 === 0 ? 'var(--wpwps-primary-light)' : 'var(--wpwps-secondary-light)',
                    filter: 'blur(' + size / 3 + 'px)',
                    opacity: Math.random() * 0.5 + 0.3,
                    top: Math.random() * 100 + '%',
                    left: Math.random() * 100 + '%',
                    transform: 'translateY(0px)',
                    transition: 'transform ' + (Math.random() * 3 + 4) + 's ease-in-out'
                });
                
                headerElement.append(particle);
                
                // Animate particle floating
                animateParticle(particle);
            }
        }
    }
    
    /**
     * Animate a particle with random floating movement
     * @param {jQuery} particle The particle element
     */
    function animateParticle(particle) {
        const moveY = Math.random() * 40 - 20; // -20px to +20px
        const duration = Math.random() * 3 + 4; // 4-7 seconds
        
        particle.css({
            'transform': 'translateY(' + moveY + 'px)',
            'transition': 'transform ' + duration + 's ease-in-out'
        });
        
        setTimeout(function() {
            animateParticle(particle);
        }, duration * 1000);
    }
    
    /**
     * Initialize premium animations for UI elements
     */
    function initPremiumAnimations() {
        // Add loading animation to metrics cards
        $('.metric-card').each(function(index) {
            const card = $(this);
            
            // Add a nice entrance animation with staggered delay
            card.css({
                'opacity': 0,
                'transform': 'translateY(20px)'
            });
            
            setTimeout(function() {
                card.css({
                    'opacity': 1,
                    'transform': 'translateY(0)',
                    'transition': 'opacity 0.6s ease-out, transform 0.6s ease-out'
                });
            }, 100 * index);
            
            // Animate the number counters
            const valueEl = card.find('.metric-value');
            if (valueEl.length) {
                const value = parseInt(valueEl.text().replace(/[^\d]/g, '')) || 0;
                const prefix = valueEl.text().replace(/[\d,]/g, '');
                
                valueEl.text('0');
                
                setTimeout(function() {
                    $({counter: 0}).animate({counter: value}, {
                        duration: 1500,
                        easing: 'swing',
                        step: function(now) {
                            valueEl.text(prefix + Math.ceil(now).toLocaleString());
                        }
                    });
                }, 500 * index);
            }
        });
        
        // Animate progress bars when in view
        $('.progress-premium .progress-bar').each(function() {
            const bar = $(this);
            const width = bar.data('width') || bar.parents('.progress-premium').data('width') || '0';
            
            bar.css('width', '0%');
            
            // Detect when element is in viewport
            checkInView(bar[0], function() {
                bar.css({
                    'width': width + '%',
                    'transition': 'width 1.5s cubic-bezier(.17,.67,.83,.67)'
                });
            });
        });
        
        // Add animation to status cards
        $('.status-card-premium').each(function(index) {
            const card = $(this);
            
            // Stagger the animation
            setTimeout(function() {
                card.addClass('animated');
            }, index * 200);
            
            // Add hover effect
            card.on('mouseenter', function() {
                const icon = card.find('.status-icon');
                icon.css({
                    'transform': 'scale(1.1) rotateZ(5deg)',
                    'transition': 'transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275)'
                });
            }).on('mouseleave', function() {
                const icon = card.find('.status-icon');
                icon.css({
                    'transform': 'scale(1) rotateZ(0deg)',
                    'transition': 'transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275)'
                });
            });
        });
    }
    
    /**
     * Initialize parallax effects for sections
     */
    function initParallaxEffects() {
        // Add parallax effect to dashboard sections
        $(window).on('scroll', function() {
            const scrollTop = $(window).scrollTop();
            
            $('.dashboard-header-premium').css({
                'transform': 'translateY(' + scrollTop * 0.2 + 'px)',
            });
            
            $('.metrics-grid').css({
                'transform': 'translateY(' + scrollTop * 0.1 + 'px)',
            });
            
            // Parallax for background shapes
            $('.wpwps-bg-shape').each(function() {
                const shape = $(this);
                const speed = shape.data('parallax-speed') || Math.random() * 0.1 + 0.05;
                shape.css({
                    'transform': 'translateY(' + scrollTop * speed + 'px) rotate(' + (scrollTop * 0.02) + 'deg)'
                });
            });
        });
    }
    
    /**
     * Initialize scroll-based animations
     */
    function initScrollEffects() {
        // Reveal elements as they come into view
        $('.fade-in-up').each(function() {
            const element = $(this);
            
            // Initially hide the element
            element.css({
                'opacity': 0,
                'transform': 'translateY(30px)'
            });
            
            // Detect when element is in viewport
            checkInView(element[0], function() {
                element.css({
                    'opacity': 1,
                    'transform': 'translateY(0)',
                    'transition': 'opacity 0.8s ease-out, transform 0.8s ease-out'
                });
            });
        });
    }
    
    /**
     * Initialize light trails effect
     */
    function initLightTrails() {
        // Add light trails to the page
        const body = $('body');
        const trailCount = 3;
        
        for (let i = 0; i < trailCount; i++) {
            const trail = $('<div class="light-trail"></div>');
            body.append(trail);
            
            animateTrail(trail);
        }
        
        function animateTrail(trail) {
            // Random positioning
            const startY = Math.random() * window.innerHeight;
            const startRotate = Math.random() * 20 - 10; // -10 to 10 degrees
            const duration = Math.random() * 5000 + 10000; // 10-15 seconds
            
            // Set initial position
            trail.css({
                'top': startY + 'px',
                'transform': 'translateX(-100%) rotate(' + startRotate + 'deg)',
                'opacity': 0,
                'animation': 'trail-move ' + duration + 'ms linear'
            });
            
            // Reset animation when it completes
            setTimeout(function() {
                animateTrail(trail);
            }, duration + 200); // Add small buffer
        }
    }
    
    /**
     * Initialize premium charts with better styling
     */
    function initPremiumCharts() {
        // Check if Chart.js exists
        if (typeof Chart === 'undefined') return;
        
        // Override default chart options for better aesthetics
        Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = 'rgba(0, 0, 0, 0.6)';
        Chart.defaults.borderColor = 'rgba(0, 0, 0, 0.05)';
        Chart.defaults.elements.line.tension = 0.4;
        Chart.defaults.elements.line.borderWidth = 2;
        Chart.defaults.elements.line.fill = true;
        Chart.defaults.elements.point.radius = 4;
        Chart.defaults.elements.point.hitRadius = 10;
        Chart.defaults.elements.point.hoverRadius = 5;
        Chart.defaults.elements.point.hoverBorderWidth = 2;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.7)';
        Chart.defaults.plugins.tooltip.bodyFont.size = 13;
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;
        Chart.defaults.plugins.tooltip.titleFont.size = 14;
        Chart.defaults.plugins.tooltip.titleFont.weight = 'bold';
        Chart.defaults.plugins.tooltip.displayColors = true;
        Chart.defaults.plugins.tooltip.boxPadding = 4;
        Chart.defaults.plugins.legend.position = 'top';
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.padding = 15;
        
        // Set improved animations
        Chart.defaults.animation = {
            duration: 2000,
            easing: 'easeOutQuart'
        };
    }
    
    /**
     * Initialize hover effects for interactive elements
     */
    function initHoverEffects() {
        // Add hover effects to buttons
        $('.btn-premium').on('mouseenter', function() {
            const btn = $(this);
            btn.css('transform', 'translateY(-3px) scale(1.02)');
            
            // Create ripple effect
            const ripple = $('<span class="btn-ripple"></span>');
            btn.append(ripple);
            
            setTimeout(function() {
                ripple.remove();
            }, 800);
        }).on('mouseleave', function() {
            $(this).css('transform', '');
        });
        
        // Add hover effects to product cards
        $('.product-card-premium').on('mouseenter', function() {
            $(this).find('.product-image').css('transform', 'scale(1.1)');
        }).on('mouseleave', function() {
            $(this).find('.product-image').css('transform', 'scale(1)');
        });
    }
    
    /**
     * Initialize premium tabs with smooth transitions
     */
    function initPremiumTabs() {
        const tabContainer = $('.premium-tabs');
        if (!tabContainer.length) return;
        
        // Add indicator element if it doesn't exist
        if (!$('.premium-tab-indicator').length) {
            tabContainer.append('<span class="premium-tab-indicator"></span>');
        }
        
        const indicator = $('.premium-tab-indicator');
        const tabs = tabContainer.find('.premium-tab');
        
        // Position indicator under active tab
        function positionIndicator(activeTab) {
            if (!activeTab.length) return;
            
            const tabPosition = activeTab.position();
            indicator.css({
                left: tabPosition.left,
                width: activeTab.outerWidth()
            });
        }
        
        // Initialize indicator position
        positionIndicator(tabs.filter('.active'));
        
        // Handle tab clicks
        tabs.on('click', function(e) {
            e.preventDefault();
            
            const tab = $(this);
            const targetId = tab.attr('href');
            
            // Update active state
            tabs.removeClass('active');
            tab.addClass('active');
            
            // Move indicator
            positionIndicator(tab);
            
            // Show corresponding content
            $('.premium-tab-content').hide();
            $(targetId).fadeIn(300);
        });
        
        // Handle window resize
        $(window).on('resize', function() {
            positionIndicator(tabs.filter('.active'));
        });
    }
    
    /**
     * Initialize page transitions for smooth navigation
     */
    function initPageTransitions() {
        // Add transition overlay if it doesn't exist
        if (!$('.page-transition-overlay').length) {
            $('body').append('<div class="page-transition-overlay"></div>');
        }
        
        const overlay = $('.page-transition-overlay');
        
        // Handle internal navigation links
        $('a[data-transition="true"]').on('click', function(e) {
            const link = $(this);
            const target = link.attr('href');
            
            // Skip if it's an external link or has no href
            if (!target || target.startsWith('#') || target.startsWith('http') || link.attr('target') === '_blank') {
                return;
            }
            
            e.preventDefault();
            
            // Show transition overlay
            overlay.addClass('active');
            
            setTimeout(function() {
                window.location = target;
            }, 500);
        });
        
        // Hide overlay on page load
        $(window).on('load', function() {
            setTimeout(function() {
                overlay.removeClass('active');
            }, 200);
        });
    }
    
    /**
     * Helper function to check if an element is in viewport
     * @param {HTMLElement} element The element to check
     * @param {Function} callback Function to call when element is in view
     */
    function checkInView(element, callback) {
        if (!element) return;
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    callback(element);
                    observer.disconnect();
                }
            });
        }, {
            threshold: 0.1 // Trigger when at least 10% of element is visible
        });
        
        observer.observe(element);
    }
})(jQuery);
