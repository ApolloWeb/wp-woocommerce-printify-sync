/**
 * API Service for WP WooCommerce Printify Sync
 * 
 * Handles all API interactions with Printify and WooCommerce
 */
(function($) {
    'use strict';
    
    // Global API service object
    window.WPWPSApiService = {
        /**
         * Get products from Printify API
         * 
         * @param {Object} options - Filter and pagination options
         * @returns {Promise} Promise that resolves to products data
         */
        getProducts: function(options) {
            return this.makeRequest('GET', 'products', options);
        },
        
        /**
         * Get a single product
         * 
         * @param {number} id - Product ID
         * @returns {Promise} Promise that resolves to product data
         */
        getProduct: function(id) {
            return this.makeRequest('GET', `products/${id}`);
        },
        
        /**
         * Get orders
         * 
         * @param {Object} options - Filter and pagination options
         * @returns {Promise} Promise that resolves to orders data
         */
        getOrders: function(options) {
            return this.makeRequest('GET', 'orders', options);
        },
        
        /**
         * Get a single order
         * 
         * @param {number} id - Order ID
         * @returns {Promise} Promise that resolves to order data
         */
        getOrder: function(id) {
            return this.makeRequest('GET', `orders/${id}`);
        },
        
        /**
         * Sync products from Printify to WooCommerce
         * 
         * @param {Array|null} productIds - Optional array of product IDs to sync, null for all
         * @returns {Promise} Promise that resolves to sync result
         */
        syncProducts: function(productIds) {
            return this.makeRequest('POST', 'sync/products', { product_ids: productIds });
        },
        
        /**
         * Process orders in Printify
         * 
         * @param {Array|null} orderIds - Optional array of order IDs to process, null for all pending
         * @returns {Promise} Promise that resolves to processing result
         */
        processOrders: function(orderIds) {
            return this.makeRequest('POST', 'process/orders', { order_ids: orderIds });
        },
        
        /**
         * Get Printify shops
         * 
         * @returns {Promise} Promise that resolves to shops data
         */
        getShops: function() {
            return this.makeRequest('GET', 'shops');
        },
        
        /**
         * Get product categories (from WooCommerce)
         * 
         * @returns {Promise} Promise that resolves to categories data
         */
        getCategories: function() {
            return this.makeRequest('GET', 'categories');
        },
        
        /**
         * Get sync status
         * 
         * @returns {Promise} Promise that resolves to sync status data
         */
        getSyncStatus: function() {
            return this.makeRequest('GET', 'sync/status');
        },
        
        /**
         * Get sales data for dashboard
         * 
         * @param {string} period - Time period (day, week, month, year)
         * @returns {Promise} Promise that resolves to sales data
         */
        getSalesData: function(period) {
            return this.makeRequest('GET', 'sales', { period: period });
        },
        
        /**
         * Makes an API request
         * 
         * @param {string} method - HTTP method (GET, POST, etc)
         * @param {string} endpoint - API endpoint
         * @param {Object} data - Request data
         * @returns {Promise} Promise that resolves to response data
         */
        makeRequest: function(method, endpoint, data) {
            // Check if we have the necessary configuration
            if (!wpwpsApi || !wpwpsApi.apiUrl) {
                console.error('API configuration is missing');
                return Promise.reject(new Error('API configuration is missing'));
            }
            
            // Create request options
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpwpsApi.nonce
                }
            };
            
            // Add request body for non-GET requests
            if (method !== 'GET' && data) {
                options.body = JSON.stringify(data);
            }
            
            // Build URL with query parameters for GET requests
            let url = `${wpwpsApi.apiUrl}/${endpoint}`;
            if (method === 'GET' && data) {
                const queryParams = new URLSearchParams();
                Object.entries(data).forEach(([key, value]) => {
                    if (Array.isArray(value)) {
                        value.forEach(item => queryParams.append(`${key}[]`, item));
                    } else {
                        queryParams.append(key, value);
                    }
                });
                url += `?${queryParams.toString()}`;
            }
            
            // Show loading indicator
            this.showLoading(endpoint);
            
            // Make the request
            return fetch(url, options)
                .then(response => {
                    // Hide loading indicator
                    this.hideLoading(endpoint);
                    
                    // Check if response is ok
                    if (!response.ok) {
                        return response.json().then(error => {
                            throw new Error(error.message || 'API request failed');
                        });
                    }
                    
                    // Parse response as JSON
                    return response.json();
                })
                .catch(error => {
                    // Hide loading indicator
                    this.hideLoading(endpoint);
                    
                    // Log error
                    console.error('API request failed:', error);
                    
                    // Re-throw error for caller to handle
                    throw error;
                });
        },
        
        /**
         * Show loading indicator for a specific endpoint
         * 
         * @param {string} endpoint - API endpoint
         */
        showLoading: function(endpoint) {
            // Add loading class to related elements
            if (endpoint.startsWith('products')) {
                $('.products-container').addClass('is-loading');
            } else if (endpoint.startsWith('orders')) {
                $('.orders-container').addClass('is-loading');
            } else {
                // Generic loading indicator
                $('body').addClass('api-loading');
            }
        },
        
        /**
         * Hide loading indicator for a specific endpoint
         * 
         * @param {string} endpoint - API endpoint
         */
        hideLoading: function(endpoint) {
            // Remove loading class from related elements
            if (endpoint.startsWith('products')) {
                $('.products-container').removeClass('is-loading');
            } else if (endpoint.startsWith('orders')) {
                $('.orders-container').removeClass('is-loading');
            } else {
                // Generic loading indicator
                $('body').removeClass('api-loading');
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Set up global error handler for API requests
        $(document).on('ajaxError', function(event, jqxhr, settings, thrownError) {
            if (settings.url && settings.url.includes(wpwpsApi.apiUrl)) {
                console.error('API error:', thrownError);
                
                // Show error notification
                if (window.wpwpsNotify) {
                    wpwpsNotify.error('API Error', thrownError || 'An error occurred during the API request');
                }
            }
        });
    });
    
})(jQuery);
