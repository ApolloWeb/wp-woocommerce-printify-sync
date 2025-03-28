<?php
/**
 * Dashboard Provider
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Providers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

/**
 * Dashboard Provider class
 */
class DashboardProvider extends ServiceProvider
{
    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        add_action('admin_menu', [$this, 'registerAdminMenu']);
        add_action('wp_ajax_wpwps_dashboard_stats', [$this, 'ajaxDashboardStats']);
    }

    /**
     * Register admin menu items
     *
     * @return void
     */
    public function registerAdminMenu()
    {
        // Main menu with t-shirt icon
        $this->addMenuPage(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            'dashicons-shirt', // T-shirt icon
            58
        );

        // Dashboard submenu
        $this->addSubmenuPage(
            'wpwps-dashboard',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard'
        );

        // Products submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-products',
            [$this, 'renderProductsPage'],
            null
        );

        // Orders submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-orders',
            [$this, 'renderOrdersPage'],
            null
        );
        
        // Support Tickets submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Support Tickets', 'wp-woocommerce-printify-sync'),
            __('Support Tickets', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'edit.php?post_type=support_ticket',
            null,
            null
        );

        // Email Queue submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Email Queue', 'wp-woocommerce-printify-sync'),
            __('Email Queue', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-email-queue',
            [$this, 'renderEmailQueuePage'],
            null
        );
        
        // Logs submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-logs',
            [$this, 'renderLogsPage'],
            null
        );

        // Settings submenu
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettingsPage'],
            null
        );
    }

    /**
     * Render the dashboard page
     *
     * @return void
     */
    public function renderPage()
    {
        $data = [
            'product_count' => $this->getProductCount(),
            'order_count' => $this->getOrderCount(),
            'sync_status' => $this->getSyncStatus(),
            'email_queue' => $this->getEmailQueueCount(),
            'webhook_status' => $this->getWebhookStatus(),
        ];

        View::render('wpwps-dashboard', $data);
    }

    /**
     * Render the products page
     *
     * @return void
     */
    public function renderProductsPage()
    {
        $data = [
            'products' => $this->getProducts(),
            'sync_status' => $this->getSyncStatus(),
        ];

        View::render('wpwps-products', $data);
    }

    /**
     * Render the orders page
     *
     * @return void
     */
    public function renderOrdersPage()
    {
        $data = [
            'orders' => $this->getOrders(),
            'sync_status' => $this->getSyncStatus(),
        ];

        View::render('wpwps-orders', $data);
    }

    /**
     * Render the email queue page
     *
     * @return void
     */
    public function renderEmailQueuePage()
    {
        $data = [
            'emails' => $this->getEmailQueue(),
        ];

        View::render('wpwps-email-queue', $data);
    }

    /**
     * Render the logs page
     *
     * @return void
     */
    public function renderLogsPage()
    {
        // Delegate to LogsProvider
        $logsProvider = $this->getProvider(\ApolloWeb\WPWooCommercePrintifySync\Providers\LogsProvider::class);
        if ($logsProvider) {
            $logsProvider->renderPage();
        } else {
            // Fallback if provider not found
            View::render('wpwps-logs', [
                'logs' => [],
                'pagination' => [],
                'stats' => [
                    'total' => 0,
                    'api' => 0,
                    'sync' => 0,
                    'webhook' => 0,
                    'error' => 0
                ],
            ]);
        }
    }

    /**
     * Render the settings page
     *
     * @return void
     */
    public function renderSettingsPage()
    {
        // Delegate to SettingsProvider
        $settingsProvider = $this->getProvider(\ApolloWeb\WPWooCommercePrintifySync\Providers\SettingsProvider::class);
        if ($settingsProvider) {
            $settingsProvider->renderPage();
        } else {
            // Fallback if provider not found
            View::render('wpwps-settings', []);
        }
    }

    /**
     * AJAX handler for dashboard stats
     *
     * @return void
     */
    public function ajaxDashboardStats()
    {
        // Check nonce and capabilities
        if (!$this->verifyNonce() || !$this->checkCapability()) {
            wp_send_json_error(['message' => __('Unauthorized access', 'wp-woocommerce-printify-sync')], 403);
        }

        $data = [
            'product_count' => $this->getProductCount(),
            'order_count' => $this->getOrderCount(),
            'sync_status' => $this->getSyncStatus(),
            'email_queue' => $this->getEmailQueueCount(),
            'webhook_status' => $this->getWebhookStatus(),
            'charts' => [
                'sales' => $this->getSalesChartData(),
                'products' => $this->getProductsChartData(),
            ],
        ];

        wp_send_json_success($data);
    }

    /**
     * Get product count
     *
     * @return int
     */
    protected function getProductCount()
    {
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get order count
     *
     * @return int
     */
    protected function getOrderCount()
    {
        $args = [
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_printify_order_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get sync status
     *
     * @return array
     */
    protected function getSyncStatus()
    {
        return [
            'products' => [
                'synced' => $this->getSyncedProductCount(),
                'total' => $this->getProductCount(),
                'last_sync' => get_option('wpwps_last_product_sync', 0),
            ],
            'orders' => [
                'synced' => $this->getSyncedOrderCount(),
                'total' => $this->getOrderCount(),
                'last_sync' => get_option('wpwps_last_order_sync', 0),
            ],
        ];
    }

    /**
     * Get synced product count
     *
     * @return int
     */
    protected function getSyncedProductCount()
    {
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_printify_is_synced',
                    'value' => '1',
                    'compare' => '=',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get synced order count
     *
     * @return int
     */
    protected function getSyncedOrderCount()
    {
        $args = [
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_printify_order_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get email queue count
     *
     * @return int
     */
    protected function getEmailQueueCount()
    {
        // This would be implemented with the actual email queue provider
        return 0;
    }

    /**
     * Get webhook status
     *
     * @return array
     */
    protected function getWebhookStatus()
    {
        return [
            'active' => get_option('wpwps_webhooks_active', false),
            'last_received' => get_option('wpwps_last_webhook_received', 0),
            'errors' => get_option('wpwps_webhook_errors', 0),
        ];
    }

    /**
     * Get products data for display
     *
     * @return array
     */
    protected function getProducts()
    {
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        $products = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product_id = get_the_ID();
                $products[] = [
                    'id' => $product_id,
                    'title' => get_the_title(),
                    'printify_id' => get_post_meta($product_id, '_printify_product_id', true),
                    'is_synced' => get_post_meta($product_id, '_printify_is_synced', true),
                    'last_synced' => get_post_meta($product_id, '_printify_last_synced', true),
                    'provider' => get_post_meta($product_id, '_printify_print_provider_name', true),
                    'edit_url' => get_edit_post_link($product_id),
                    'view_url' => get_permalink($product_id),
                ];
            }
            wp_reset_postdata();
        }

        return $products;
    }

    /**
     * Get orders data for display
     *
     * @return array
     */
    protected function getOrders()
    {
        $args = [
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'posts_per_page' => 10,
            'meta_query' => [
                [
                    'key' => '_printify_order_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        $orders = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $order_id = get_the_ID();
                $order = wc_get_order($order_id);
                
                if ($order) {
                    $orders[] = [
                        'id' => $order_id,
                        'order_number' => $order->get_order_number(),
                        'printify_id' => get_post_meta($order_id, '_printify_order_id', true),
                        'status' => get_post_meta($order_id, '_printify_order_status', true),
                        'date_created' => $order->get_date_created()->date_i18n(get_option('date_format')),
                        'total' => $order->get_formatted_order_total(),
                        'edit_url' => get_edit_post_link($order_id),
                    ];
                }
            }
            wp_reset_postdata();
        }

        return $orders;
    }

    /**
     * Get email queue data
     *
     * @return array
     */
    protected function getEmailQueue()
    {
        // This would be implemented with the actual email queue provider
        return [];
    }

    /**
     * Get sales chart data
     *
     * @return array
     */
    protected function getSalesChartData()
    {
        $days = 30;
        $labels = [];
        $data = [];
        $printify_data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date_i18n(get_option('date_format'), strtotime($date));
            $data[] = $this->getDailySales($date);
            $printify_data[] = $this->getDailyPrintifyCost($date);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('Sales', 'wp-woocommerce-printify-sync'),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                    'data' => $data,
                ],
                [
                    'label' => __('Printify Cost', 'wp-woocommerce-printify-sync'),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1,
                    'data' => $printify_data,
                ],
            ],
        ];
    }

    /**
     * Get products chart data
     *
     * @return array
     */
    protected function getProductsChartData()
    {
        global $wpdb;
        
        // Get top 5 products by sales
        $query = $wpdb->prepare("
            SELECT p.post_title as name, SUM(order_item_meta__qty.meta_value) as count
            FROM {$wpdb->prefix}woocommerce_order_items as order_items
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta__qty ON order_items.order_item_id = order_item_meta__qty.order_item_id
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta__product_id ON order_items.order_item_id = order_item_meta__product_id.order_item_id
            LEFT JOIN {$wpdb->posts} as p ON order_item_meta__product_id.meta_value = p.ID
            LEFT JOIN {$wpdb->postmeta} as pm ON p.ID = pm.post_id AND pm.meta_key = '_printify_product_id'
            WHERE order_items.order_item_type = 'line_item'
            AND order_item_meta__qty.meta_key = '_qty'
            AND order_item_meta__product_id.meta_key = '_product_id'
            AND pm.meta_value IS NOT NULL
            GROUP BY p.ID
            ORDER BY count DESC
            LIMIT 5
        ");
        
        $results = $wpdb->get_results($query);
        
        $labels = [];
        $data = [];
        
        foreach ($results as $result) {
            $labels[] = $result->name;
            $data[] = (int) $result->count;
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('Top Selling Products', 'wp-woocommerce-printify-sync'),
                    'backgroundColor' => [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                    ],
                    'borderColor' => [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                    ],
                    'borderWidth' => 1,
                    'data' => $data,
                ],
            ],
        ];
    }

    /**
     * Get daily sales amount
     *
     * @param string $date Date in Y-m-d format
     * @return float
     */
    protected function getDailySales($date)
    {
        $args = [
            'post_type' => 'shop_order',
            'post_status' => ['wc-completed', 'wc-processing'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => [
                [
                    'year' => date('Y', strtotime($date)),
                    'month' => date('m', strtotime($date)),
                    'day' => date('d', strtotime($date)),
                ],
            ],
            'meta_query' => [
                [
                    'key' => '_printify_order_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        $total = 0;

        foreach ($query->posts as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $total += $order->get_total();
            }
        }

        return $total;
    }

    /**
     * Get daily Printify cost
     *
     * @param string $date Date in Y-m-d format
     * @return float
     */
    protected function getDailyPrintifyCost($date)
    {
        $args = [
            'post_type' => 'shop_order',
            'post_status' => ['wc-completed', 'wc-processing'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => [
                [
                    'year' => date('Y', strtotime($date)),
                    'month' => date('m', strtotime($date)),
                    'day' => date('d', strtotime($date)),
                ],
            ],
            'meta_query' => [
                [
                    'key' => '_printify_order_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        $total = 0;

        foreach ($query->posts as $order_id) {
            $shipping_cost = get_post_meta($order_id, '_printify_shipping_cost_usd', true);
            $shipping_cost = $shipping_cost ? (float) $shipping_cost : 0;

            // Add item costs
            $items_cost = 0;
            $order = wc_get_order($order_id);
            if ($order) {
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    $cost = get_post_meta($product_id, '_printify_cost_price', true);
                    $cost = $cost ? (float) $cost : 0;
                    $items_cost += $cost * $item->get_quantity();
                }
            }

            $total += $shipping_cost + $items_cost;
        }

        return $total;
    }
}