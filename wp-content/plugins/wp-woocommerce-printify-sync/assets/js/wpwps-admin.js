(function($) {
    'use strict';

    // Toast notification system
    const wpwpsToast = {
        container: null,
        init() {
            this.container = $('<div/>', {
                class: 'wpwps-toast-container position-fixed top-0 end-0 p-3'
            }).appendTo('body');
        },
        show(message, type = 'info') {
            const toast = $(`
                <div class="toast wpwps-toast wpwps-animate-slide" role="alert">
                    <div class="toast-header">
                        <i class="fas fa-${this.getIcon(type)} me-2"></i>
                        <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">${message}</div>
                </div>
            `);
            
            this.container.append(toast);
            const bsToast = new bootstrap.Toast(toast[0], {
                animation: true,
                delay: 4000,
                autohide: true
            });
            bsToast.show();
            
            toast.on('hidden.bs.toast', () => {
                toast.addClass('wpwps-animate-fade-out');
                setTimeout(() => toast.remove(), 300);
            });
        },
        getIcon(type) {
            const icons = {
                success: 'check-circle',
                error: 'exclamation-circle',
                warning: 'exclamation-triangle',
                info: 'info-circle'
            };
            return icons[type] || 'info-circle';
        }
    };

    // Sidebar toggle
    function initSidebar() {
        const sidebar = $('.wpwps-sidebar');
        $('.wpwps-sidebar-toggle').on('click', () => {
            sidebar.toggleClass('collapsed');
            $('body').toggleClass('sidebar-collapsed');
        });
    }

    // Search functionality
    function initSearch() {
        const searchInput = $('.wpwps-search input');
        searchInput.on('input', function() {
            // TODO: Implement AJAX search
        });
    }

    // Widget refresh functionality
    const widgetManager = {
        init() {
            try {
                this.setupRefreshButtons();
                this.setupAutoRefresh();
                this.setupA11y();
            } catch (error) {
                console.error('Widget manager initialization failed:', error);
            }
        },

        setupRefreshButtons() {
            $('.wpwps-widget-refresh').on('click', function(e) {
                e.preventDefault();
                const widget = $(this).closest('.wpwps-widget');
                widgetManager.refreshWidget(widget);
            });
        },

        refreshWidget(widget) {
            widget.addClass('wpwps-loading wpwps-blur')
                 .attr('aria-busy', 'true')
                 .attr('aria-live', 'polite');
            
            // Check if GSAP is available
            if (typeof gsap !== 'undefined') {
                gsap.to(widget, {
                    scale: 0.98,
                    opacity: 0.8,
                    duration: 0.3,
                    ease: 'power2.inOut'
                });

                setTimeout(() => {
                    gsap.to(widget, {
                        scale: 1,
                        opacity: 1,
                        duration: 0.5,
                        ease: 'elastic.out(1, 0.5)',
                        onComplete: () => {
                            widget.removeClass('wpwps-loading wpwps-blur');
                            widget.attr('aria-busy', 'false');
                            $(document).trigger('wpwps-widget-update', {
                                message: 'Widget content has been refreshed'
                            });
                        }
                    });
                }, 1000);
            } else {
                // Fallback animation using jQuery
                widget.animate({ opacity: 0.8 }, 300, function() {
                    setTimeout(() => {
                        widget.animate({ opacity: 1 }, 500, function() {
                            widget.removeClass('wpwps-loading wpwps-blur');
                            widget.attr('aria-busy', 'false');
                            $(document).trigger('wpwps-widget-update', {
                                message: 'Widget content has been refreshed'
                            });
                        });
                    }, 1000);
                });
            }
        },

        setupAutoRefresh() {
            setInterval(() => {
                $('.wpwps-widget[data-auto-refresh="true"]').each(function() {
                    widgetManager.refreshWidget($(this));
                });
            }, 30000); // 30 seconds
        },

        setupA11y() {
            // Make widgets keyboard accessible
            $('.wpwps-widget-refresh').attr('role', 'button')
                .attr('tabindex', '0')
                .on('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        $(this).trigger('click');
                    }
                });

            // Announce widget updates
            const announcer = $('<div/>', {
                'class': 'visually-hidden',
                'aria-live': 'polite',
                'role': 'status'
            }).appendTo('body');

            $(document).on('wpwps-widget-update', function(e, data) {
                announcer.text(data.message || 'Widget updated');
            });
        }
    };

    // Layout transitions
    const layoutManager = {
        init() {
            this.setupPageTransitions();
            this.setupWidgetAnimations();
        },

        setupPageTransitions() {
            $(document).on('click', '.wpwps-nav-link', function(e) {
                e.preventDefault();
                const target = $(this).attr('href');
                $('.wpwps-content').addClass('wpwps-animate-fade-out');
                setTimeout(() => window.location = target, 300);
            });
        },

        setupWidgetAnimations() {
            const widgets = $('.wpwps-widget');
            widgets.each(function(index) {
                $(this).css('animation-delay', `${index * 0.1}s`)
                    .addClass('wpwps-animate-slide');
            });
        }
    };

    // Premium UI Effects Manager
    const uiEffects = {
        init() {
            try {
                if (typeof particlesJS !== 'undefined') {
                    this.initParticles();
                }
                this.initParallax();
                this.initMorphing();
                if (typeof gsap !== 'undefined') {
                    this.setupPremiumAnimations();
                }
            } catch (error) {
                console.error('UI effects initialization failed:', error);
            }
        },

        initParticles() {
            $('.wpwps-particle-bg').each(function() {
                particlesJS(this.id, {
                    particles: {
                        number: { value: 30, density: { enable: true, value_area: 800 } },
                        color: { value: '#96588a' },
                        opacity: { value: 0.1 },
                        size: { value: 3 },
                        move: { enable: true, speed: 1 }
                    }
                });
            });
        },

        initParallax() {
            $('.wpwps-widget').on('mousemove', function(e) {
                const card = $(this);
                const rect = card[0].getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateY = -((x - centerX) / centerX) * 3;
                const rotateX = ((y - centerY) / centerY) * 3;
                
                card.css('transform', `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`);
            }).on('mouseleave', function() {
                $(this).css('transform', '');
            });
        },

        initMorphing() {
            $('.wpwps-btn').on('mouseenter', function() {
                $(this).addClass('wpwps-btn-morphing');
            }).on('mouseleave', function() {
                $(this).removeClass('wpwps-btn-morphing');
            });
        },

        setupPremiumAnimations() {
            // Stagger widget animations on page load
            gsap.from('.wpwps-widget', {
                duration: 0.8,
                y: 30,
                opacity: 0,
                stagger: 0.1,
                ease: 'power3.out'
            });

            // Smooth number counting
            $('.wpwps-stats-number').each(function() {
                const $this = $(this);
                const value = parseFloat($this.text());
                $this.prop('Counter', 0).animate({
                    Counter: value
                }, {
                    duration: 2000,
                    easing: 'swing',
                    step: function(now) {
                        $this.text(Math.ceil(now).toLocaleString());
                    }
                });
            });
        }
    };

    // Accessibility Manager
    const a11yManager = {
        init() {
            this.setupKeyboardNav();
            this.setupFocusManagement();
            this.setupAriaUpdates();
        },

        setupKeyboardNav() {
            // Enable keyboard navigation for sidebar
            $('.wpwps-sidebar .nav-link').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });

            // Enable keyboard navigation for dropdowns
            $('.dropdown-menu').on('keydown', '.dropdown-item', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });
        },

        setupFocusManagement() {
            // Trap focus in modals
            $('.modal').on('shown.bs.modal', function() {
                $(this).find('[autofocus]').focus();
            });

            // Return focus after modal close
            $('.modal').on('hidden.bs.modal', function() {
                $(this).data('trigger-element').focus();
            });

            // Focus management for dropdowns
            $('.dropdown').on('shown.bs.dropdown', function() {
                $(this).find('.dropdown-menu').find('a, button').first().focus();
            });
        },

        setupAriaUpdates() {
            // Update ARIA states for dynamic content
            $(document).on('wpwps-content-update', function(e, data) {
                if (data.target) {
                    const target = $(data.target);
                    target.attr('aria-busy', 'true');
                    
                    // After content update
                    setTimeout(() => {
                        target.attr('aria-busy', 'false');
                        target.find('[role="alert"]').attr('aria-live', 'polite');
                    }, 100);
                }
            });
        }
    };

    // Initialize all components with error handling
    $(document).ready(() => {
        try {
            wpwpsToast.init();
            initSidebar();
            initSearch();
            widgetManager.init();
            layoutManager.init();
            
            // Initialize UI effects only if dependencies are available
            if (typeof gsap !== 'undefined' || typeof particlesJS !== 'undefined') {
                uiEffects.init();
            }
            
            // Initialize Bootstrap components
            $('[data-bs-toggle="tooltip"]').tooltip({
                delay: { show: 50, hide: 50 },
                animation: true
            });
            
            $('[data-bs-toggle="popover"]').popover({
                trigger: 'hover',
                animation: true
            });

            // Add hover animations to interactive elements
            $('.wpwps-btn, .wpwps-card, .wpwps-widget').addClass('wpwps-hover-lift');

            // Smooth scroll behavior
            $('html').css('scroll-behavior', 'smooth');

            // Welcome animation (remove in production)
            setTimeout(() => {
                $('.wpwps-welcome-message').addClass('wpwps-animate-slide');
            }, 500);

            // Add premium loading indicator
            $(document).ajaxStart(() => {
                $('.wpwps-loading-overlay').addClass('active');
            }).ajaxStop(() => {
                $('.wpwps-loading-overlay').removeClass('active');
            });

            // Initialize accessibility manager
            a11yManager.init();
            
            // Add skip link for keyboard users
            $('body').prepend(`
                <a href="#wpwps-main" class="wpwps-skip-link visually-hidden-focusable">
                    Skip to main content
                </a>
            `);
        } catch (error) {
            console.error('Plugin initialization failed:', error);
        }
    });

    // Export for global access
    window.wpwpsToast = wpwpsToast;
    window.wpwpsWidgetManager = widgetManager;
})(jQuery);
