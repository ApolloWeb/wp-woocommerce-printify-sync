<?php
/**
 * Ticket Custom Post Type
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\CustomPostTypes
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\CustomPostTypes;

class Ticket {
    private static $instance = null;
    private $timestamp = '2025-03-05 19:07:18';
    private $user = 'ApolloWeb';
    private $post_type = 'wpwprintifysync_ticket';
    
    /**
     * Get single instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register the ticket custom post type
        add_action('init', [$this, 'registerPostType']);
        
        // Register custom taxonomies
        add_action('init', [$this, 'registerTaxonomies']);
        
        // Register meta boxes
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
        
        // Save ticket data
        add_action('save_post_' . $this->post_type, [$this, 'saveTicketData'], 10, 3);
        
        // Add custom columns to admin list
        add_filter('manage_' . $this->post_type . '_posts_columns', [$this, 'setTicketColumns']);
        add_action('manage_' . $this->post_type . '_posts_custom_column', [$this, 'renderTicketColumn'], 10, 2);
        
        // Add sortable columns
        add_filter('manage_edit-' . $this->post_type . '_sortable_columns', [$this, 'setSortableColumns']);
        
        // Filter by custom taxonomies
        add_action('restrict_manage_posts', [$this, 'filterByTaxonomy']);
        
        // Add custom status filter
        add_action('restrict_manage_posts', [$this, 'addStatusFilter']);
        add_filter('parse_query', [$this, 'filterByStatus']);
    }
    
    /**
     * Register ticket custom post type
     */
    public function registerPostType() {
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
        ];
        
        $args = [
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => 'wpwprintifysync-settings',
            'query_var'           => true,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => ['title', 'editor', 'author', 'comments', 'custom-fields'],
            'menu_icon'           => 'dashicons-tickets-alt',
            'map_meta_cap'        => true,
            'show_in_rest'        => true,
        ];
        
        register_post_type($this->post_type, $args);
        
        // Register custom statuses
        $this->registerCustomStatuses();
    }
    
    /**
     * Register custom ticket statuses
     */
    private function registerCustomStatuses() {
        register_post_status('ticket-new', [
            'label'                     => _x('New', 'Ticket status', 'wp-woocommerce-printify-sync'),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'label_count'               => _n_noop('New <span class="count">(%s)</span>', 'New <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('ticket-open', [
            'label'                     => _x('Open', 'Ticket status', 'wp-woocommerce-printify-sync'),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'label_count'               => _n_noop('Open <span class="count">(%s)</span>', 'Open <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('ticket-pending', [
            'label'                     => _x('Pending', 'Ticket status', 'wp-woocommerce-printify-sync'),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'label_count'               => _n_noop('Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('ticket-resolved', [
            'label'                     => _x('Resolved', 'Ticket status', 'wp-woocommerce-printify-sync'),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'label_count'               => _n_noop('Resolved <span class="count">(%s)</span>', 'Resolved <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('ticket-closed', [
            'label'                     => _x('Closed', 'Ticket status', 'wp-woocommerce-printify-sync'),
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'label_count'               => _n_noop('Closed <span class="count">(%s)</span>', 'Closed <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Register custom taxonomies for tickets
     */
    public function registerTaxonomies() {
        // Ticket Type Taxonomy
        $labels = [
            'name'              => _x('Ticket Types', 'taxonomy general name', 'wp-woocommerce-printify-sync'),
            'singular_name'     => _x('Ticket Type', 'taxonomy singular name', 'wp-woocommerce-printify-sync'),
            'search_items'      => __('Search Ticket Types', 'wp-woocommerce-printify-sync'),
            'all_items'         => __('All Ticket Types', 'wp-woocommerce-printify-sync'),
            'parent_item'       => __('Parent Ticket Type', 'wp-woocommerce-printify-sync'),
            'parent_item_colon' => __('Parent Ticket Type:', 'wp-woocommerce-printify-sync'),
            'edit_item'         => __('Edit Ticket Type', 'wp-woocommerce-printify-sync'),
            'update_item'       => __('Update Ticket Type', 'wp-woocommerce-printify-sync'),
            'add_new_item'      => __('Add New Ticket Type', 'wp-woocommerce-printify-sync'),
            'new_item_name'     => __('New Ticket Type Name', 'wp-woocommerce-printify-sync'),
            'menu_name'         => __('Ticket Types', 'wp-woocommerce-printify-sync'),
        ];

        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
        ];

        register_taxonomy('ticket_type', [$this->post_type], $args);
        
        // Predefined ticket types
        $this->ensureTermExists('ticket_type', 'refund-request', 'Refund Request');
        $this->ensureTermExists('ticket_type', 'reprint-request', 'Reprint Request');
        $this->ensureTermExists('ticket_type', 'shipping-issue', 'Shipping Issue');
        $this->ensureTermExists('ticket_type', 'product-issue', 'Product Issue');
        $this->ensureTermExists('ticket_type', 'general-inquiry', 'General Inquiry');
        $this->ensureTermExists('ticket_type', 'system-notification', 'System Notification');
        
        // Ticket Priority Taxonomy
        $labels = [
            'name'              => _x('Priorities', 'taxonomy general name', 'wp-woocommerce-printify-sync'),
            'singular_name'     => _x('Priority', 'taxonomy singular name', 'wp-woocommerce-printify-sync'),
            'search_items'      => __('Search Priorities', 'wp-woocommerce-printify-sync'),
            'all_items'         => __('All Priorities', 'wp-woocommerce-printify-sync'),
            'edit_item'         => __('Edit Priority', 'wp-woocommerce-printify-sync'),
            'update_item'       => __('Update Priority', 'wp-woocommerce-printify-sync'),
            'add_new_item'      => __('Add New Priority', 'wp-woocommerce-printify-sync'),
            'new_item_name'     => __('New Priority Name', 'wp-woocommerce-printify-sync'),
            'menu_name'         => __('Priorities', 'wp-woocommerce-printify-sync'),
        ];

        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
        ];

        register_taxonomy('ticket_priority', [$this->post_type], $args);
        
        // Predefined priorities
        $this->ensureTermExists('ticket_priority', 'low', 'Low');
        $this->ensureTermExists('ticket_priority', 'normal', 'Normal');
        $this->ensureTermExists('ticket_priority', 'high', 'High');
        $this->ensureTermExists('ticket_priority', 'critical', 'Critical');
    }
    
    /**
     * Ensure taxonomy term exists
     *
     * @param string $taxonomy Taxonomy name
     * @param string $slug Term slug
     * @param string $name Term name
     */
    private function ensureTermExists($taxonomy, $slug, $name) {
        if (!term_exists($slug, $taxonomy)) {
            wp_insert_term($name, $taxonomy, ['slug' => $slug]);
        }
    }
    
    /**
     * Register meta boxes for ticket edit screen
     */
    public function registerMetaBoxes() {
        add_meta_box(
            'wpwprintifysync_ticket_details',
            __('Ticket Details', 'wp-woocommerce-printify-sync'),
            [$this, 'renderTicketDetailsMetaBox'],
            $this->post_type,
            'side',
            'high'
        );
        
        add_meta_box(
            'wpwprintifysync_ticket_communication',
            __('Ticket Communication', 'wp-woocommerce-printify-sync'),
            [$this, 'renderTicketCommunicationMetaBox'],
            $this->post_type,
            'normal',
            'high'
        );
        
        add_meta_box(
            'wpwprintifysync_ticket_order',
            __('Related Order', 'wp-woocommerce-printify-sync'),
            [$this, 'renderTicketOrderMetaBox'],
            $this->post_type,
            'normal',
            'high'
        );
        
        add_meta_box(
            'wpwprintifysync_ticket_attachments',
            __('Attachments', 'wp-woocommerce-printify-sync'),
            [$this, 'renderTicketAttachmentsMetaBox'],
            $this->post_type,
            'normal',
            'high'
        );
    }
    
    /**
     * Render ticket details meta box
     */
    public function renderTicketDetailsMetaBox($post) {
        // Add nonce for security
        wp_nonce_field('wpwprintifysync_save_ticket_data', 'wpwprintifysync_ticket_nonce');
        
        // Get ticket data
        $customer_email = get_post_meta($post->ID, '_ticket_customer_email', true);
        $customer_name = get_post_meta($post->ID, '_ticket_customer_name', true);
        $created_at = get_post_meta($post->ID, '_ticket_created_at', true);
        $updated_at = get_post_meta($post->ID, '_ticket_updated_at', true);
        $source = get_post_meta($post->ID, '_ticket_source', true);
        
        // If dates are empty, use post dates
        if (empty($created_at)) {
            $created_at = $post->post_date;
        }
        
        if (empty($updated_at)) {
            $updated_at = $post->post_modified;
        }
        
        // Format dates
        $created_at_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($created_at));
        $updated_at_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($updated_at));
        
        // Output fields
        ?>
        <p>
            <label for="ticket_customer_email"><?php _e('Customer Email:', 'wp-woocommerce-printify-sync'); ?></label><br>
            <input type="email" id="ticket_customer_email" name="ticket_customer_email" value="<?php echo esc_attr($customer_email); ?>" class="widefat">
        </p>
        
        <p>
            <label for="ticket_customer_name"><?php _e('Customer Name:', 'wp-woocommerce-printify-sync'); ?></label><br>
            <input type="text" id="ticket_customer_name" name="ticket_customer_name" value="<?php echo esc_attr($customer_name); ?>" class="widefat">
        </p>
        
        <p>
            <label><?php _e('Created:', 'wp-woocommerce-printify-sync'); ?></label><br>
            <?php echo esc_html($created_at_formatted); ?>
        </p>
        
        <p>
            <label><?php _e('Last Updated:', 'wp-woocommerce-printify-sync'); ?></label><br>
            <?php echo esc_html($updated_at_formatted); ?>
        </p>
        
        <p>
            <label><?php _e('Source:', 'wp-woocommerce-printify-sync'); ?></label><br>
            <?php echo esc_html($source ? $source : __('Manual', 'wp-woocommerce-printify-sync')); ?>
        </p>
        
        <p>
            <label for="ticket_status"><?php _e('Status:', 'wp-woocommerce-printify-sync'); ?></label><br>
            <select id="ticket_status" name="ticket_status" class="widefat">
                <option value="ticket-new" <?php selected($post->post_status, 'ticket-new'); ?>><?php _e('New', 'wp-woocommerce-printify-sync'); ?></option>
                <option value="ticket-open" <?php selected($post->post_status, 'ticket-open'); ?>><?php _e('Open', 'wp-woocommerce-printify-sync'); ?></option>
                <option value="ticket-pending" <?php selected($post->post_status, 'ticket-pending'); ?>><?php _e('Pending', 'wp-