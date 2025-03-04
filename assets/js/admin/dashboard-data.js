/**
 * Dashboard Data Handler
 * Provides dummy data for dashboard widgets during development
 */

(function($) {
    'use strict';
    
    // Current date for reference
    const currentDate = new Date('2025-03-04T13:25:25Z');
    
    /**
     * Generate dummy data for dashboard
     */
    const DashboardData = {
        /**
         * Get static statistics
         */
        getStatistics: function() {
            return {
                activeShops: 3,
                syncedProducts: 245,
                recentOrders: 18,
                lastSync: this.formatTimeAgo(new Date(currentDate - 7200000)) // 2 hours ago
            };
        },
        
        /**
         * Get sales chart data
         */
        getSalesChartData: function(period = 'week') {
            let labels = [];
            let salesData = [];
            let ordersData = [];
            
            switch(period) {
                case 'day':
                    // Last 24 hours
                    for (let i = 0; i < 24; i++) {
                        labels.push(i + ':00');
                        salesData.push(Math.floor(Math.random() * 800) + 200);
                        ordersData.push(Math.floor(Math.random() * 10) + 1);
                    }
                    break;
                    
                case 'week':
                    // Last 7 days
                    for (let i = 6; i >= 0; i--) {
                        const date = new Date(currentDate);
                        date.setDate(date.getDate() - i);
                        labels.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
                        salesData.push(Math.floor(Math.random() * 2000) + 500);
                        ordersData.push(Math.floor(Math.random() * 20) + 5);
                    }
                    break;
                    
                case 'month':
                    // Last 30 days
                    for (let i = 29; i >= 0; i--) {
                        const date = new Date(currentDate);
                        date.setDate(date.getDate() - i);
                        labels.push(date.getDate());
                        salesData.push(Math.floor(Math.random() * 2000) + 500);
                        ordersData.push(Math.floor(Math.random() * 20) + 5);
                    }
                    break;
                    
                case 'year':
                    // Last 12 months
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    for (let i = 11; i >= 0; i--) {
                        const monthIndex = (currentDate.getMonth() - i + 12) % 12;
                        labels.push(months[monthIndex]);
                        salesData.push(Math.floor(Math.random() * 25000) + 5000);
                        ordersData.push(Math.floor(Math.random() * 200) + 50);
                    }
                    break;
            }
            
            return {
                labels: labels,
                datasets: [
                    {
                        label: 'Sales ($)',
                        data: salesData,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Orders',
                        data: ordersData,
                        borderColor: '#65b32e',
                        backgroundColor: 'rgba(101, 179, 46, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            };
        },
        
        /**
         * Get recent products data
         */
        getRecentProducts: function() {
            const products = [
                { id: 1, name: 'Customized T-Shirt', status: 'synced', sync_date: '2025-03-04 12:30:25' },
                { id: 2, name: 'Logo Hoodie', status: 'synced', sync_date: '2025-03-04 11:15:10' },
                { id: 3, name: 'Coffee Mug', status: 'failed', sync_date: '2025-03-04 10:05:42' },
                { id: 4, name: 'Phone Case', status: 'synced', sync_date: '2025-03-04 09:45:15' },
                { id: 5, name: 'Canvas Print', status: 'syncing', sync_date: '2025-03-04 08:30:00' }
            ];
            
            return products;
        },
        
        /**
         * Get recent orders data
         */
        getRecentOrders: function() {
            const orders = [
                { id: '#12345', customer: 'John Smith', total: '$78.50', status: 'processing', date: '2025-03-04 13:15:20' },
                { id: '#12344', customer: 'Amy Johnson', total: '$124.95', status: 'completed', date: '2025-03-04 11:23:45' },
                { id: '#12343', customer: 'Mark Williams', total: '$45.00', status: 'processing', date: '2025-03-04 09:10:30' },
                { id: '#12342', customer: 'Susan Brown', total: '$89.99', status: 'failed', date: '2025-03-03 22:05:15' },
                { id: '#12341', customer: 'David Miller', total: '$134.50', status: 'completed', date: '2025-03-03 15:45:10' }
            ];
            
            return orders;
        },
        
        /**
         * Format time ago string
         */
        formatTimeAgo: function(date) {
            const seconds = Math.floor((currentDate - date) / 1000);
            
            let interval = Math.floor(seconds / 31536000);
            if (interval >= 1) {
                return interval + ' year' + (interval > 1 ? 's' : '') + ' ago';
            }
            
            interval = Math.floor(seconds / 2592000);
            if (interval >= 1) {
                return interval + ' month' + (interval > 1 ? 's' : '') + ' ago';
            }
            
            interval = Math.floor(seconds / 86400);
            if (interval >= 1) {
                return interval + ' day' + (interval > 1 ? 's' : '') + ' ago';
            }
            
            interval = Math.floor(seconds / 3600);
            if (interval >= 1) {
                return interval + ' hour' + (interval > 1 ? 's' : '') + ' ago';
            }
            
            interval = Math.floor(seconds / 60);
            if (interval >= 1) {
                return interval + ' minute' + (interval > 1 ? 's' : '') + ' ago';
            }
            
            return Math.floor(seconds) + ' second' + (seconds > 1 ? 's' : '') + ' ago';
        }
    };
    
    // Expose to global scope for development use
    window.PrintifySyncDummyData = DashboardData;
    
})(jQuery);