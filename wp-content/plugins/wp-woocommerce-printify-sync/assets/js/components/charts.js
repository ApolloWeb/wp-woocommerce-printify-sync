window.WPWPS = window.WPWPS || {};

WPWPS.Charts = {
    SyncStats: class {
        constructor(selector) {
            this.chart = new Chart(document.querySelector(selector), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Products Synced',
                        data: [],
                        borderColor: '#2271b1',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        update(data) {
            // Implementation
        }
    },

    ProductTypes: class {
        constructor(selector) {
            this.chart = new Chart(document.querySelector(selector), {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#2271b1',
                            '#8c8f94',
                            '#d63638'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        update(data) {
            // Implementation
        }
    }
};