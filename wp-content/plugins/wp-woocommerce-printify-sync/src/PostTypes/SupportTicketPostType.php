<?php

namespace ApolloWeb\WPWooCommercePrintifySync\PostTypes;

/**
 * Class SupportTicketPostType
 * 
 * Handles registration and configuration of the support_ticket custom post type
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync\PostTypes
 */
class SupportTicketPostType
{
    /**
     * Post type name.
     * 
     * @var string
     */
    private $postType = 'support_ticket';
    
    /**
     * Register the support_ticket custom post type
     * 
     * @return void
     */
    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'registerTaxonomies']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveMetaBoxes']);
        add_filter('manage_support_ticket_posts_columns', [$this, 'defineColumns']);
        add_action('manage_support_ticket_posts_custom_column', [$this, 'populateColumns'], 10, 2);
        add_filter('manage_edit-support_ticket_sortable_columns', [$this, 'sortableColumns']);
    }
    
    /**
     * Register the support_ticket post type
     * 
     * @return void
     */
    public function registerPostType(): void
    {
        $labels = [
            'name'                  => _x('Support Tickets', 'Post type general name', 'wp-woocommerce-printify-sync'),
            'singular_name'         => _x('Support Ticket', 'Post type singular name', 'wp-woocommerce-printify-sync'),
            'menu_name'             => _x('Support Tickets', 'Admin Menu text', 'wp-woocommerce-printify-sync'),
            'name_admin_bar'        => _x('Support Ticket', 'Add New on Toolbar', 'wp-woocommerce-printify-sync'),
            'add_new'               => __('Add New', 'wp-woocommerce-printify-sync'),
            'add_new_item'          => __('Add New Ticket', 'wp-woocommerce-printify-sync'),
            'new_item'              => __('New Ticket', 'wp-woocommerce-printify-sync'),
            'edit_item'             => __('Edit Ticket', 'wp-woocommerce-printify-sync'),
            'view_item'             => __('View Ticket', 'wp-woocommerce-printify-sync'),
            'all_items'             => __('All Tickets', 'wp-woocommerce-printify-sync'),
            'search_items'          => __('Search Tickets', 'wp-woocommerce-printify-sync'),
            'parent_item_colon'     => __('Parent Tickets:', 'wp-woocommerce-printify-sync'),
            'not_found'             => __('No tickets found.', 'wp-woocommerce-printify-sync'),
            'not_found_in_trash'    => __('No tickets found in Trash.', 'wp-woocommerce-printify-sync'),
            'featured_image'        => _x('Ticket Cover Image', 'Overrides the "Featured Image" phrase', 'wp-woocommerce-printify-sync'),
            'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase', 'wp-woocommerce-printify-sync'),
            'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase', 'wp-woocommerce-printify-sync'),
            'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase', 'wp-woocommerce-printify-sync'),
            'archives'              => _x('Ticket archives', 'The post type archive label used in nav menus', 'wp-woocommerce-printify-sync'),
            'insert_into_item'      => _x('Insert into ticket', 'Overrides the "Insert into post"/"Insert into page" phrase', 'wp-woocommerce-printify-sync'),
            'uploaded_to_this_item' => _x('Uploaded to this ticket', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'wp-woocommerce-printify-sync'),
            'filter_items_list'     => _x('Filter tickets list', 'Screen reader text for the filter links heading on the post type listing screen', 'wp-woocommerce-printify-sync'),
            'items_list_navigation' => _x('Tickets list navigation', 'Screen reader text for the pagination heading on the post type listing screen', 'wp-woocommerce-printify-sync'),
            'items_list'            => _x('Tickets list', 'Screen reader text for the items list heading on the post type listing screen', 'wp-woocommerce-printify-sync'),
        ];

        $args = [
            'labels'              => $labels,
            'description'         => __('Support tickets for customer inquiries and issues', 'wp-woocommerce-printify-sync'),
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => 'wpwps-dashboard',
            'query_var'           => true,
            'rewrite'             => ['slug' => 'support-ticket'],
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => ['title', 'editor', 'author', 'revisions'],
            'menu_icon'           => 'dashicons-email',
            'show_in_rest'        => true,
        ];

        register_post_type($this->postType, $args);
    }
    
    /**
     * Register taxonomies for the support_ticket post type
     * 
     * @return void
     */
    public function registerTaxonomies(): void
    {
        // Category Taxonomy
        register_taxonomy('ticket_category', [$this->postType], [
            'hierarchical'      => true,
            'labels'            => [
                'name'                       => _x('Ticket Categories', 'taxonomy general name', 'wp-woocommerce-printify-sync'),
                'singular_name'              => _x('Ticket Category', 'taxonomy singular name', 'wp-woocommerce-printify-sync'),
                'search_items'               => __('Search Ticket Categories', 'wp-woocommerce-printify-sync'),
                'popular_items'              => __('Popular Ticket Categories', 'wp-woocommerce-printify-sync'),
                'all_items'                  => __('All Ticket Categories', 'wp-woocommerce-printify-sync'),
                'parent_item'                => null,
                'parent_item_colon'          => null,
                'edit_item'                  => __('Edit Ticket Category', 'wp-woocommerce-printify-sync'),
                'update_item'                => __('Update Ticket Category', 'wp-woocommerce-printify-sync'),
                'add_new_item'               => __('Add New Ticket Category', 'wp-woocommerce-printify-sync'),
                'new_item_name'              => __('New Ticket Category Name', 'wp-woocommerce-printify-sync'),
                'separate_items_with_commas' => __('Separate ticket categories with commas', 'wp-woocommerce-printify-sync'),
                'add_or_remove_items'        => __('Add or remove ticket categories', 'wp-woocommerce-printify-sync'),
                'choose_from_most_used'      => __('Choose from the most used ticket categories', 'wp-woocommerce-printify-sync'),
                'not_found'                  => __('No ticket categories found.', 'wp-woocommerce-printify-sync'),
                'menu_name'                  => __('Categories', 'wp-woocommerce-printify-sync'),
            ],
            'show_ui'           => true,
            'show_admin_column'  => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'ticket-category'],
            'show_in_rest'      => true,
        ]);
        
        // Status Taxonomy
        register_taxonomy('ticket_status', [$this->postType], [
            'hierarchical'      => false,
            'labels'            => [
                'name'                       => _x('Ticket Statuses', 'taxonomy general name', 'wp-woocommerce-printify-sync'),
                'singular_name'              => _x('Ticket Status', 'taxonomy singular name', 'wp-woocommerce-printify-sync'),
                'search_items'               => __('Search Ticket Statuses', 'wp-woocommerce-printify-sync'),
                'popular_items'              => __('Popular Ticket Statuses', 'wp-woocommerce-printify-sync'),
                'all_items'                  => __('All Ticket Statuses', 'wp-woocommerce-printify-sync'),
                'edit_item'                  => __('Edit Ticket Status', 'wp-woocommerce-printify-sync'),
                'update_item'                => __('Update Ticket Status', 'wp-woocommerce-printify-sync'),
                'add_new_item'               => __('Add New Ticket Status', 'wp-woocommerce-printify-sync'),
                'new_item_name'              => __('New Ticket Status Name', 'wp-woocommerce-printify-sync'),
                'separate_items_with_commas' => __('Separate ticket statuses with commas', 'wp-woocommerce-printify-sync'),
                'add_or_remove_items'        => __('Add or remove ticket statuses', 'wp-woocommerce-printify-sync'),
                'choose_from_most_used'      => __('Choose from the most used ticket statuses', 'wp-woocommerce-printify-sync'),
                'not_found'                  => __('No ticket statuses found.', 'wp-woocommerce-printify-sync'),
                'menu_name'                  => __('Statuses', 'wp-woocommerce-printify-sync'),
            ],
            'show_ui'           => true,
            'show_admin_column'  => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'ticket-status'],
            'show_in_rest'      => true,
        ]);
        
        // Urgency Taxonomy
        register_taxonomy('ticket_urgency', [$this->postType], [
            'hierarchical'      => false,
            'labels'            => [
                'name'                       => _x('Ticket Urgency', 'taxonomy general name', 'wp-woocommerce-printify-sync'),
                'singular_name'              => _x('Ticket Urgency', 'taxonomy singular name', 'wp-woocommerce-printify-sync'),
                'search_items'               => __('Search Ticket Urgency Levels', 'wp-woocommerce-printify-sync'),
                'popular_items'              => __('Popular Ticket Urgency Levels', 'wp-woocommerce-printify-sync'),
                'all_items'                  => __('All Ticket Urgency Levels', 'wp-woocommerce-printify-sync'),
                'edit_item'                  => __('Edit Ticket Urgency', 'wp-woocommerce-printify-sync'),
                'update_item'                => __('Update Ticket Urgency', 'wp-woocommerce-printify-sync'),
                'add_new_item'               => __('Add New Ticket Urgency', 'wp-woocommerce-printify-sync'),
                'new_item_name'              => __('New Ticket Urgency Name', 'wp-woocommerce-printify-sync'),
                'separate_items_with_commas' => __('Separate ticket urgency levels with commas', 'wp-woocommerce-printify-sync'),
                'add_or_remove_items'        => __('Add or remove ticket urgency levels', 'wp-woocommerce-printify-sync'),
                'choose_from_most_used'      => __('Choose from the most used ticket urgency levels', 'wp-woocommerce-printify-sync'),
                'not_found'                  => __('No ticket urgency levels found.', 'wp-woocommerce-printify-sync'),
                'menu_name'                  => __('Urgency', 'wp-woocommerce-printify-sync'),
            ],
            'show_ui'           => true,
            'show_admin_column'  => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'ticket-urgency'],
            'show_in_rest'      => true,
        ]);
    }
    
    /**
     * Add meta boxes for the support_ticket post type
     * 
     * @return void
     */
    public function addMetaBoxes(): void
    {
        add_meta_box(
            'ticket_details',
            __('Ticket Details', 'wp-woocommerce-printify-sync'),
            [$this, 'renderDetailsMetabox'],
            $this->postType,
            'normal',
            'high'
        );
        
        add_meta_box(
            'customer_info',
            __('Customer Information', 'wp-woocommerce-printify-sync'),
            [$this, 'renderCustomerMetabox'],
            $this->postType,
            'side',
            'default'
        );
        
        add_meta_box(
            'order_info',
            __('Order Information', 'wp-woocommerce-printify-sync'),
            [$this, 'renderOrderMetabox'],
            $this->postType,
            'side',
            'default'
        );
        
        add_meta_box(
            'ai_analysis',
            __('AI Analysis', 'wp-woocommerce-printify-sync'),
            [$this, 'renderAIMetabox'],
            $this->postType,
            'normal',
            'default'
        );
        
        add_meta_box(
            'ticket_response',
            __('Ticket Response', 'wp-woocommerce-printify-sync'),
            [$this, 'renderResponseMetabox'],
            $this->postType,
            'normal',
            'high'
        );
    }
    
    /**
     * Render the ticket details metabox
     * 
     * @param \WP_Post $post The post object
     * @return void
     */
    public function renderDetailsMetabox(\WP_Post $post): void
    {
        // Implement metabox rendering
        wp_nonce_field('wpwps_ticket_details_nonce', 'ticket_details_nonce');
        
        $email_from = get_post_meta($post->ID, '_ticket_email_from', true);
        $email_date = get_post_meta($post->ID, '_ticket_email_date', true);
        $message_id = get_post_meta($post->ID, '_ticket_message_id', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ticket_email_from"><?php _e('From', 'wp-woocommerce-printify-sync'); ?></label></th>
                <td><input type="text" id="ticket_email_from" name="ticket_email_from" value="<?php echo esc_attr($email_from); ?>" class="regular-text" readonly /></td>
            </tr>
            <tr>
                <th><label for="ticket_email_date"><?php _e('Date', 'wp-woocommerce-printify-sync'); ?></label></th>
                <td><input type="text" id="ticket_email_date" name="ticket_email_date" value="<?php echo esc_attr($email_date); ?>" class="regular-text" readonly /></td>
            </tr>
            <tr>
                <th><label for="ticket_message_id"><?php _e('Message ID', 'wp-woocommerce-printify-sync'); ?></label></th>
                <td><input type="text" id="ticket_message_id" name="ticket_message_id" value="<?php echo esc_attr($message_id); ?>" class="regular-text" readonly /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save meta box data
     * 
     * @param int $post_id The post ID
     * @return void
     */
    public function saveMetaBoxes(int $post_id): void
    {
        // Implement meta box saving
        // Add security checks and validation
    }
}
