<?phpnamespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;class ApiCallLogsWidget extends AbstractWidget
{
    public static function render()
    {
        $data = [
            'api_calls' => [
                ['id' => 1, 'request' => 'GET /products', 'status' => 'Success'],
                ['id' => 2, 'request' => 'POST /orders', 'status' => 'Failure']
            ]
        ];        self::getTemplate('api-call-logs-widget', $data);
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
