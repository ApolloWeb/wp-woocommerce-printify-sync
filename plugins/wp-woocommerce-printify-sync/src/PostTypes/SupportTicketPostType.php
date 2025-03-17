<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\PostTypes;

class SupportTicketPostType
{
    public const POST_TYPE = 'support_ticket';
    public const TAXONOMY_CATEGORY = 'ticket_category';
    public const TAXONOMY_STATUS = 'ticket_status';

    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'registerTaxonomies']);
        add_filter('postie_post_type', [$this, 'setPostiePostType'], 10, 2);
    }

    public function registerPostType(): void
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Support Ticket', 'wp-woocommerce-printify-sync'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'comments', 'custom-fields'],
            'menu_icon' => 'dashicons-tickets-alt',
            'has_archive' => true,
            'hierarchical' => false,
            'show_in_menu' => true,
        ]);
    }

    public function registerTaxonomies(): void
    {
        register_taxonomy(self::TAXONOMY_CATEGORY, [self::POST_TYPE], [
            'labels' => [
                'name' => __('Ticket Categories', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Ticket Category', 'wp-woocommerce-printify-sync'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
        ]);

        register_taxonomy(self::TAXONOMY_STATUS, [self::POST_TYPE], [
            'labels' => [
                'name' => __('Ticket Statuses', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Ticket Status', 'wp-woocommerce-printify-sync'),
            ],
            'hierarchical' => false,
            'show_in_rest' => true,
            'show_admin_column' => true,
        ]);
    }

    public function setPostiePostType($post_type, $email): string
    {
        // Check if email is support related
        if ($this->isValidSupportEmail($email)) {
            return self::POST_TYPE;
        }
        return $post_type;
    }

    private function isValidSupportEmail($email): bool
    {
        // Check if email is sent to support email address
        $supportEmail = get_option('wpwps_support_email');
        return strpos($email['to'], $supportEmail) !== false;
    }
}