<?phpnamespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;class StockLevelsWidget extends AbstractWidget
{
    public static function render()
    {
        $data = [
            'stock_levels' => [
                'in_stock' => 300,
                'low_stock' => 50,
                'out_of_stock' => 20
            ]
        ];        self::getTemplate('stock-levels-widget', $data);
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
