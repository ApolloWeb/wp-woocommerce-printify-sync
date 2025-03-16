(function($) {
    'use strict';

    const WPWPS_ExchangeRates = {
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initModals();
            this.initCharts();
        },

        bindEvents: function() {
            $('#updateRates').on('click', this.handleUpdateRates.bind(this));
            $('.view-history').on('click', this.handleViewHistory.bind(this));
            $('#rateHistoryModal .btn-group button').on('click', this.handlePeriodChange.bind(this));
        },

        initTooltips: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        },

        initModals: function() {
            this.historyModal = new bootstrap.Modal('#rateHistoryModal');
        },

        initCharts: function() {
            this.chartColors = {
                primary: '#674399',
                success: '#28a745',
                danger: '#dc3545',
                border: '#e9ecef',
                text: '#6c757d'
            };

            this.chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 0
                        }
                    },
                    y: {
                        grid: {
                            borderDash: [2, 2]
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(4);
                            }
                        }
                    }
                }
            };
        },

        handleUpdateRates: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);

            this.showLoading($button);

            $.ajax({
                url: wpwpsExchangeRates.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_update_rates',
                    nonce: wpwpsExchangeRates.nonce
                },
                success: response => {
                    if (response.success) {
                        this.showToast('success', response.data.message);
                        this.updateRatesDisplay(response.data.rates);
                    } else {
                        this.showToast('error', response.data.message);
                    }
                },
                error: () => {
                    this.showToast('error', wpwpsExchangeRates.i18n.error);
                },
                complete: () => {
                    this.hideLoading($button);
                }
            });
        },

        handleViewHistory: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const currency = $button.data('currency');
            
            this.currentCurrency = currency;
            $('.currency-pair').text(`${wpwpsExchangeRates.baseCurrency}/${currency}`);
            
            this.loadRateHistory(currency, '30d');
            this.historyModal.show();
        },

        handlePeriodChange: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const period = $button.data('period');

            $button.siblings().removeClass('active');
            $button.addClass('active');

            this.loadRateHistory(this.currentCurrency, period);
        },

        loadRateHistory: function(currency, period) {
            const $container = $('.chart-container');
            this.showLoading($container);

            $.ajax({
                url: wpwpsExchangeRates.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_get_rate_history',
                    nonce: wpwpsExchangeRates.nonce,
                    currency: currency,
                    period: period
                },
                success: response => {
                    if (response.success) {
                        this.renderRateHistoryChart(response.data.history);
                    } else {
                        this.showToast('error', response.data.message);
                    }
                },
                error: () => {
                    this.showToast('error', wpwpsExchangeRates.i18n.error);
                },
                complete: () => {
                    this.hideLoading($container);
                }
            });
        },

        renderRateHistoryChart: function(history) {
            if (this.rateHistoryChart) {
                this.rateHistoryChart.destroy();
            }

            const ctx = document.getElementById('rateHistoryChart').getContext('2d');
            
            this.rateHistoryChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: history.dates,
                    datasets: [{
                        label: 'Exchange Rate',
                        data: history.rates,
                        borderColor: this.chartColors.primary,
                        backgroundColor: this.chartColors.primary + '20',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: this.chartOptions
            });
        },

        updateRatesDisplay: function(rates) {
            Object.entries(rates).forEach(([code, rate]) => {
                if (typeof rate === 'number') {
                    $(`.current-rate[data-currency="${code}"]`).text(rate.toFixed(4));
                }
            });
        },

        showToast: function(type, message) {
            const toast = $(`
                <div class="toast align-items-center text-white bg-${type}" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `);

            $('.toast-container').append(toast);
            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();

            toast.on('hidden.bs.toast', () => toast.remove());
        },

        showLoading: function(selector) {
            const $el = $(selector);
            $el.prop('disabled', true)
               .addClass('position-relative')
               .append('<div class="loading-overlay"><div class="spinner-border"></div></div>');
        },

        hideLoading: function(selector) {
            const $el = $(selector);
            $el.prop('disabled', false)
               .removeClass('position-relative')
               .find('.loading-overlay')
               .remove();
        }
    };

    $(document).ready(() => WPWPS_ExchangeRates.init());

})(jQuery);