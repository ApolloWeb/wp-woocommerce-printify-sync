private function registerCustomOrderStatuses(): void
{
    add_action('init', function() {
        register_post_status('wc-refund-requested', [
            'label' => 'Refund Requested',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop(
                'Refund Requested <span class="count">(%s)</span>',
                'Refund Requested <span class="count">(%s)</span>'
            )
        ]);

        // Register other custom statuses...
    });

    add_filter('wc_order_statuses', function($order_statuses) {
        $new_statuses = [
            'wc-refund-requested' => 'Refund Requested',
            'wc-reprint-requested' => 'Reprint Requested',
            'wc-refund-approved' => 'Refund Approved',
            'wc-reprint-approved' => 'Reprint Approved',
            'wc-refund-denied' => 'Refund Denied',
            'wc-awaiting-evidence' => 'Awaiting Evidence',
            'wc-evidence-submitted' => 'Evidence Submitted'
        ];
        
        return array_merge($order_statuses, $new_statuses);
    });
}