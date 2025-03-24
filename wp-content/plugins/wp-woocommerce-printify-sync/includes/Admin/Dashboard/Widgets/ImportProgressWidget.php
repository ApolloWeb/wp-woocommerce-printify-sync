<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard\AbstractWidget;

class ImportProgressWidget extends AbstractWidget
{
    protected $id = 'import_progress';
    protected $title = 'Product Import Progress';

    protected function getData(): array
    {
        $queue = \ActionScheduler::store()->search([
            'group' => 'wpwps_product_import',
            'per_page' => -1,
        ]);

        $total = get_option('wpwps_total_products_to_import', 0);
        $imported = get_option('wpwps_imported_products_count', 0);
        
        return [
            'total' => $total,
            'imported' => $imported,
            'pending' => count($queue),
            'progress' => $total > 0 ? ($imported / $total) * 100 : 0
        ];
    }
}
