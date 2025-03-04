<?phpnamespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;class IncomingTicketsWidget extends AbstractWidget
{
    public static function render()
    {
        $data = [
            'tickets' => [
                ['id' => 1, 'type' => 'Refund Request', 'subject' => 'Ticket 1', 'status' => 'Open'],
                ['id' => 2, 'type' => 'Product Inquiry', 'subject' => 'Ticket 2', 'status' => 'Closed']
            ]
        ];        self::getTemplate('incoming-tickets-widget', $data);
    }
} Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: } Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------
#
#
# Commit Hash 16c804f
#
