document.addEventListener('alpine:init', () => {
    Alpine.data('dashboard', () => ({
        status: {
            state: 'active',
            message: 'Monitoring imports...'
        },
        activeImports: 0,
        activities: [],
        stats: {
            successful: 0,
            pending: 0,
            processing: 0,
            failed: 0
        },
        products: [],
        productFilter: 'all',
        isRefreshing: false,

        init() {
            this.initializeData();
            this.startPolling();
        },

        async initializeData() {
            await this.fetchStats();
            await this.fetchActivities();
            await this.fetchProducts();
        },

        startPolling() {
            setInterval(() => {
                this.fetchStats();
                this.fetchActivities();
            }, 10000);
        },

        async fetchStats() {
            this.isRefreshing = true;
            try {
                const response = await fetch(`${wpwps.ajaxurl}?action=wpwps_get_stats&nonce=${wpwps.nonce}`);
                const data = await response.json();
                if (data.success) {
                    this.stats = data.stats;
                    this.activeImports = data.stats.processing;
                }
            } catch (error) {
                console.error('Failed to fetch stats:', error);
            }
            this.isRefreshing = false;
        },

        async fetchActivities() {
            try {
                const response = await fetch(`${wpwps.ajaxurl}?action=wpwps_get_activities&nonce=${wpwps.nonce}`);
                const data = await response.json();
                if (data.success) {
                    this.activities = data.activities;
                }
            } catch (error) {
                console.error('Failed to fetch activities:', error);
            }
        },

        async fetchProducts() {
            try {
                const response = await fetch(`${wpwps.ajaxurl}?action=wpwps_get_products&nonce=${wpwps.nonce}`);
                const data = await response.json();
                if (data.success) {
                    this.products = data.products;
                }
            } catch (error) {
                console.error('Failed to fetch products:', error);
            }
        },

        get filteredProducts() {
            if (this.productFilter === 'all') return this.products;
            return this.products.filter(p => p.status === this.productFilter);
        },

        formatTime(timestamp) {
            const date = new Date(timestamp);
            return new Intl.RelativeTimeFormat('en', { numeric: 'auto' })
                .format(Math.round((date - new Date()) / 60000), 'minute');
        },

        formatPrice(price) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(price);
        },

        getActivityIcon(type) {
            const icons = {
                import: 'dashicons dashicons-download',
                update: 'dashicons dashicons-update',
                error: 'dashicons dashicons-warning'
            };
            return icons[type] || icons.import;
        },

        refreshStats() {
            this.fetchStats();
        }
    }));
});