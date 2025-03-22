                    gap: 10px;
                    margin-bottom: 15px;
                }
                
                .wpwps-status-item {
                    padding: 8px 0;
                    border-bottom: 1px solid #f0f0f1;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .wpwps-status-label {
                    font-weight: 500;
                    color: #646970;
                }
                
                .wpwps-status-value {
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                }
                
                .wpwps-status-indicator {
                    display: inline-block;
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    margin-right: 6px;
                }
                
                .wpwps-status-success {
                    background-color: #28a745;
                    box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2);
                }
                
                .wpwps-status-danger {
                    background-color: #dc3545;
                    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
                }
                
                .wpwps-badge {
                    display: inline-block;
                    padding: 2px 8px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 500;
                    color: #fff;
                }
                
                .wpwps-badge-primary {
                    background-color: #96588a;
                }
                
                .wpwps-widget-footer {
                    margin-top: 15px;
                    text-align: right;
                }
            </style>
            <?php
        } catch (\Exception $e) {
            $this->logger->error('Error rendering status widget: ' . $e->getMessage());
            echo '<p>' . esc_html__('Error loading widget data.', 'wp-woocommerce-printify-sync') . '</p>';
        }
    }
    
    /**
     * Render the Recent Printify Orders widget.
     *
     * @return void
     */
    public function renderRecentOrdersWidget()
    {
        try {
            // Get recent orders with Printify products
            $orders = $this->getRecentPrintifyOrders(5);
            
            if (empty($orders)) {
                echo '<p>' . esc_html__('No recent Printify orders found.', 'wp-woocommerce-printify-sync') . '</p>';
                return;
            }
            
            ?>
            <div class="wpwps-dashboard-widget">
                <table class="wpwps-orders-table widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Order', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php esc_html_e('Date', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php esc_html_e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php esc_html_e('Total', 'wp-woocommerce-printify-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($order->get_id())); ?>">
                                        #<?php echo esc_html($order->get_order_number()); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format'))); ?>
                                </td>
                                <td>
                                    <?php echo $this->getStatusBadge($order->get_status()); ?>
                                </td>
                                <td>
                                    <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="wpwps-widget-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-orders')); ?>" class="button">
                        <?php esc_html_e('View All Orders', 'wp-woocommerce-printify-sync'); ?>
                    </a>
                </div>
            </div>
            
            <style>
                .wpwps-orders-table {
                    border-collapse: collapse;
                    width: 100%;
                    font-family: 'Inter', sans-serif;
                }
                
                .wpwps-orders-table th {
                    text-align: left;
                    padding: 8px;
                    background-color: #f8f9fa;
                    border-bottom: 1px solid #e1e1e1;
                    font-weight: 600;
                    font-size: 13px;
                    color: #23282d;
                }
                
                .wpwps-orders-table td {
                    padding: 10px 8px;
                    border-bottom: 1px solid #f0f0f1;
                    font-size: 13px;
                }
                
                .wpwps-orders-table tr:hover td {
                    background-color: #f9f9f9;
                }
                
                .wpwps-widget-footer {
                    margin-top: 15px;
                    text-align: right;
                }
            </style>
            <?php
        } catch (\Exception $e) {
            $this->logger->error('Error rendering recent orders widget: ' . $e->getMessage());
            echo '<p>' . esc_html__('Error loading widget data.', 'wp-woocommerce-printify-sync') . '</p>';
        }
    }
    
    /**
     * Get the total number of Printify products.
     *
     * @return int Product count.
     */
    private function getProductCount()
    {
        global $wpdb;
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' 
            AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish')"
        );
        
        return $count ? (int) $count : 0;
    }
    
    /**
     * Get the total number of orders with Printify products.
     *
     * @return int Order count.
     */
    private function getOrderCount()
    {
        global $wpdb;
        
        $count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT(post_id)) FROM {$wpdb->postmeta} 
            WHERE meta_key IN ('_printify_order_id', '_printify_product_id') 
            AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order')"
        );
        
        return $count ? (int) $count : 0;
    }
    
    /**
     * Get recent orders with Printify products.
     *
     * @param int $limit Number of orders to return.
     * @return array WC_Order objects.
     */
    private function getRecentPrintifyOrders($limit = 5)
    {
        $args = [
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'relation' => 'OR',
                    [
                        'key' => '_printify_order_id',
                        'compare' => 'EXISTS',
                    ],
                    [
                        'key' => '_printify_product_id',
                        'compare' => 'EXISTS',
                    ],
                ],
            ],
            'return' => 'objects',
        ];
        
        return wc_get_orders($args);
    }
    
    /**
     * Get HTML for status badge.
     *
     * @param string $status Order status.
     * @return string HTML for status badge.
     */
    private function getStatusBadge($status)
    {
        $status_labels = [
            'processing' => __('Processing', 'wp-woocommerce-printify-sync'),
            'completed' => __('Completed', 'wp-woocommerce-printify-sync'),
            'on-hold' => __('On Hold', 'wp-woocommerce-printify-sync'),
            'cancelled' => __('Cancelled', 'wp-woocommerce-printify-sync'),
            'refunded' => __('Refunded', 'wp-woocommerce-printify-sync'),
            'pending' => __('Pending', 'wp-woocommerce-printify-sync'),
            'failed' => __('Failed', 'wp-woocommerce-printify-sync'),
        ];
        
        $badge_classes = [
            'processing' => 'wpwps-badge wpwps-badge-primary',
            'completed' => 'wpwps-badge wpwps-badge-success',
            'on-hold' => 'wpwps-badge wpwps-badge-warning',
            'cancelled' => 'wpwps-badge wpwps-badge-danger',
            'refunded' => 'wpwps-badge wpwps-badge-info',
            'pending' => 'wpwps-badge wpwps-badge-warning',
            'failed' => 'wpwps-badge wpwps-badge-danger',
        ];
        
        $label = isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
        $class = isset($badge_classes[$status]) ? $badge_classes[$status] : 'wpwps-badge wpwps-badge-secondary';
        
        return sprintf('<span class="%s">%s</span>', esc_attr($class), esc_html($label));
    }
}
