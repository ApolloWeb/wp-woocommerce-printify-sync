                            e.preventDefault();
                            if (self.currentPage < pages) {
                                self.currentPage++;
                                self.loadData();
                            }
                        });
                        pagination.append(nextButton);
                        
                        // Add pagination to container and setup event handlers
                        this.pagination.append(pagination);
                        
                        // Page number links
                        this.pagination.find('.page-link[data-page]').on('click', function(e) {
                            e.preventDefault();
                            var page = parseInt($(this).data('page'));
                            if (page !== self.currentPage) {
                                self.currentPage = page;
                                self.loadData();
                            }
                        });
                    }
                };
                
                // Initialize table
                table.init();
            });
        </script>
        <?php
    }
    
    /**
     * Render pagination
     *
     * @param int $current_page Current page
     * @param int $total_pages Total pages
     * @param string $base_url Base URL
     */
    public static function render_pagination($current_page, $total_pages, $base_url = '') {
        if ($total_pages <= 1) {
            return;
        }
        
        ?>
        <nav aria-label="<?php esc_attr_e('Page navigation', 'wp-woocommerce-printify-sync'); ?>">
            <ul class="pagination justify-content-center">
                <?php if ($current_page > 1) : ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', ($current_page - 1), $base_url)); ?>" aria-label="<?php esc_attr_e('Previous', 'wp-woocommerce-printify-sync'); ?>">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php else : ?>
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true" aria-label="<?php esc_attr_e('Previous', 'wp-woocommerce-printify-sync'); ?>">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                if ($start_page > 1) : ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', 1, $base_url)); ?>">1</a>
                    </li>
                    <?php if ($start_page > 2) : ?>
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">...</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                    <li class="page-item<?php echo $i === $current_page ? ' active' : ''; ?>">
                        <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $i, $base_url)); ?>">
                            <?php echo esc_html($i); ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages) : ?>
                    <?php if ($end_page < $total_pages - 1) : ?>
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">...</a>
                        </li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', $total_pages, $base_url)); ?>"><?php echo esc_html($total_pages); ?></a>
                    </li>
                <?php endif; ?>
                
                <?php if ($current_page < $total_pages) : ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo esc_url(add_query_arg('paged', ($current_page + 1), $base_url)); ?>" aria-label="<?php esc_attr_e('Next', 'wp-woocommerce-printify-sync'); ?>">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php else : ?>
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true" aria-label="<?php esc_attr_e('Next', 'wp-woocommerce-printify-sync'); ?>">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php
    }
    
    /**
     * AJAX handler for loading table data
     */
    public static function load_table_data() {
        check_ajax_referer('wpwprintifysync-theme', 'nonce');
        
        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : '';
        $source_action = isset($_POST['source_action']) ? sanitize_text_field($_POST['source_action']) : '';
        
        // Handle different data sources
        switch ($source) {
            case 'products':
                \ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents\DummyData::get_dummy_products();
                break;
                
            case 'orders':
                \ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents\DummyData::get_dummy_orders();
                break;
                
            case 'tickets':
                \ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents\DummyData::get_dummy_tickets();
                break;
                
            default:
                wp_send_json_error(__('Invalid data source', 'wp-woocommerce-printify-sync'));
                break;
        }
        
        // This should not be reached as each data source function should exit
        wp_send_json_error(__('Unknown error', 'wp-woocommerce-printify-sync'));
    }
}