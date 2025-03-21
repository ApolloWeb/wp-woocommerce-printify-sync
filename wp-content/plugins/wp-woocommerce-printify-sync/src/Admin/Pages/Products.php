// ...existing code...
    /**
     * Get products via AJAX.
     *
     * @return void
     */
    public function getProducts() {
        check_ajax_referer('wpwps_products_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
        }

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        // Build query.
        global $wpdb;
        $where = ["p.post_type = 'product'"];

        if ($status) {
            $where[] = $wpdb->prepare("p.post_status = %s", 'wc-' . $status);
        }

        if ($search) {
            $where[] = $wpdb->prepare(
                "(p.ID LIKE %s OR p.post_title LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $where = implode(' AND ', $where);

        // Get total count.
        $total = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            WHERE {$where}
        ");

        // Get products.
        $offset = ($page - 1) * $per_page;
        $products = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID as product_id,
                p.post_title as title,
                p.post_status as status,
                pm.meta_value as printify_id
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_printify_product_id'
            WHERE {$where}
            GROUP BY p.ID
            ORDER BY p.post_date DESC
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        wp_send_json_success([
            'products' => $products,
            'total' => $total,
            'pages' => ceil($total / $per_page),
        ]);
    }
// ...existing code...
