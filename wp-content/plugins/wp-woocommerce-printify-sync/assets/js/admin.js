(function($) {
    'use strict';

    const WPWPS = {
        init: function() {
            this.initTimestamp();
            this.initTooltips();
            this.initModals();
            this.initForms();
            this.initCharts();
            this.initAnimations();
        },

        initTimestamp: function() {
            const updateTimestamp = () => {
                $('.wpwps-timestamp-value').text(
                    new Date().toISOString().replace('T', ' ').substr(0, 19)
                );
            };
            setInterval(updateTimestamp, 1000);
            updateTimestamp();
        },

        initTooltips: function() {
            $('[data-bs-toggle="tooltip"]').tooltip({
                template: `
                    <div class="tooltip wpwps-tooltip" role="tooltip">
                        <div class="tooltip-arrow"></div>
                        <div class="tooltip-inner"></div>
                    </div>
                `
            });
        },

        initModals: function() {
            $('.wpwps-modal').on('show.bs.modal', function(e) {
                $(this).addClass('animate__animated animate__fadeIn');
            });
        },

        initForms: function() {
            $('.wpwps-form-control').on('focus', function() {
                $(this).closest('.wpwps-form-group').addClass('focused');
            }).on('blur', function() {
                $(this).closest('.wpwps-form-group').removeClass('focused');
            });
        },

        initCharts: function() {
            $('.wpwps-chart').each(function() {
                const ctx = this.getContext('2d');
                const data = $(this).data('chart');
                
                new Chart(ctx, {
                    type: data.type || 'line',
                    data: data.data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            });
        },

        initAnimations: function() {
            const animateOnScroll = () => {
                $('.wpwps-animate').each(function() {
                    const element = $(this);
                    if (this.getBoundingClientRect().top < window.innerHeight) {
                        element.addClass('animate__animated ' + element.data('animation'));
                    }
                });
            };

            $(window).on('scroll', animateOnScroll);
            animateOnScroll();
        },

        showAlert: function(message, type = 'info') {
            const alert = $(`
                <div class="wpwps-alert wpwps-alert-${type} animate__animated animate__fadeInDown">
                    ${message}
                    <button type="button" class="wpwps-close" data-dismiss="alert">
                        <i class="material-icons">close</i>
                    </button>
                </div>
            `);

            $('.wpwps-alerts-container').append(alert);

            setTimeout(() => {
                alert.addClass('animate__fadeOutUp');
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }
    };

    $(document).ready(() => WPWPS.init());

})(jQuery);