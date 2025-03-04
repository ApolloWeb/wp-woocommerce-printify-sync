<?phpnamespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;class OrdersOverviewWidget extends AbstractWidget
{
    public static function render()
    {
        // Data for the graph can be more complex; this is just an example
        $data = [
            'orders_today' => 10,
            'orders_week' => 50,
            'orders_month' => 200
        ];        self::getTemplate('orders-overview-widget', $data);
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
