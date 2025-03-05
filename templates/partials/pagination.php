<?php
/**
 * Pagination template part
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Get pagination variables
$current_page = isset($current_page) ? $current_page : $paged;
$total_pages = isset($total_pages) ? $total_pages : 1;
$total_items = isset($total_items) ? $total_items : 0;

// Create pagination links
$page_links = paginate_links([
    'base' => add_query_arg('paged', '%#%'),
    'format' => '',
    'prev_text' => __('&laquo; Previous', 'wp-woocommerce-printify-sync'),
    'next_text' => __('Next &raquo;', 'wp-woocommerce-printify-sync'),
    'total' => $total_pages,
    'current' => $current_page
]);
?>

<div class="tablenav-pages">
    <?php if ($page_links): ?>
        <span class="pagination-links"><?php echo $page_links; ?></span>
    <?php endif; ?>
    
    <span class="displaying-num">
        <?php echo sprintf(_n('%s item', '%s items', $total_items, 'wp-woocommerce-printify-sync'), number_format_i18n($total_items)); ?>
    </span>
</div>