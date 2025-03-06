<?php
/**
 * Abstract Admin Page.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Abstract class for admin pages
 */
abstract class AbstractPage {

    /**
     * Page slug
     *
     * @var string
     */
    protected $page_slug;

    /**
     * Template path
     *
     * @var string
     */
    protected $template_path;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
        $this->init_hooks();
    }

    /**
     * Initialize variables
     */
    abstract protected function init();

    /**
     * Initialize hooks
     */
    protected function init_hooks() {
        add_action( "printify_sync_render_{$this->page_slug}_page", array( $this, 'render' ) );
        add_action( 'wp_ajax_printify_sync_' . $this->page_slug, array( $this, 'handle_ajax' ) );
    }

    /**
     * Get template variables
     *
     * @return array
     */
    protected function get_template_vars() {
        return array();
    }

    /**
     * Render the admin page
     */
    public function render() {
        $template_vars = $this->get_template_vars();
        
        if ( ! empty( $template_vars ) ) {
            extract( $template_vars );
        }

        include PRINTIFY_SYNC_PATH . $this->template_path;
    }

    /**
     * Handle AJAX requests
     */
    public function handle_ajax() {
        check_ajax_referer( 'printify_sync_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You don\'t have permission to do this.', 'wp-woocommerce-printify-sync' ) ) );
        }

        $action = isset( $_REQUEST['action_type'] ) ? sanitize_text_field( $_REQUEST['action_type'] ) : '';
        
        switch ( $action ) {
            default:
                wp_send_json_error( array( 'message' => __( 'Invalid action.', 'wp-woocommerce-printify-sync' ) ) );
                break;
        }
    }
}