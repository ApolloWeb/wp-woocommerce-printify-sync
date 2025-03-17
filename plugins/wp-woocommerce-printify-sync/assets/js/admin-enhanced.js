class PrintifyAdmin {
    constructor() {
        this.initializeComponents();
        this.bindEvents();
        this.setupAutoRefresh();
    }

    initializeComponents() {
        // Initialize tooltips
        this.initTooltips();
        
        // Initialize search functionality
        this.initSearch();
        
        // Initialize filters
        this.initFilters();

        // Initialize charts if they exist
        this.initCharts();
    }

    initTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            new bootstrap.Tooltip(element);
        });
    }

    initSearch() {
        const searchInput = document.querySelector('.printify-search input');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.performSearch(searchInput.value);
            }, 300));
        }
    }

    initFilters() {
        document.querySelectorAll('.printify-filter').forEach(filter => {
            filter.addEventListener('click', () => {
                this.toggleFilter(filter);
            });
        });
    }

    initCharts() {
        // Initialize Chart.js charts if they exist
        const chartCanvas = document.getElementById('statsChart');
        if (chartCanvas) {
            this.createChart(chartCanvas);
        }
    }

    createChart(canvas) {
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.getLastNDays(7),
                datasets: [{
                    label: 'Sales',
                    data: this.generateRandomData(7),
                    borderColor: '#6366f1',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(99, 102, 241, 0.1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    bindEvents() {
        // Handle card animations
        document.querySelectorAll('.printify-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });

        // Handle notifications
        document.querySelectorAll('.printify-notification').forEach(notification => {
            setTimeout(() => {
                notification.remove();
            }, 5000);
        });
    }

    setupAutoRefresh() {
        // Auto refresh stats every minute
        setInterval(() => {
            this.refreshStats();
        }, 60000);
    }

    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `printify-notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    debounce(func, wait) {
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

    getLastNDays(n) {
        const dates = [];
        for (let i = n - 1; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            dates.push(date.toLocaleDateString());
        }
        return dates;
    }

    generateRandomData(n) {
        return Array.from({length: n}, () => Math.floor(Math.random() * 100));
    }

    // Add this to your existing JS class
    async refreshStats() {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'refresh_printify_stats',
                    nonce: printifySync.nonce
                })
            });

            const data = await response.json();
            if (data.success) {
                this.updateStats(data.stats);
            }
        } catch (error) {
            console.error('Failed to refresh stats:', error);
        }
    }

    updateStats(stats) {
        Object.entries(stats).forEach(([key, value]) => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                element.textContent = value;
                // Add a brief highlight effect
                element.classList.add('highlight');
                setTimeout(() => {
                    element.classList.remove('highlight');
                }, 1000);
            }
        });
    }
}

// Initialize on document load
document.addEventListener('DOMContentLoaded', () => {
    new PrintifyAdmin();
});