public function getDashboardLayout(): array {
    $default_layout = [
        'sales_chart' => ['x' => 0, 'y' => 0, 'w' => 12, 'h' => 6], // Full width sales chart at top
        'sync_status' => ['x' => 0, 'y' => 6, 'w' => 6, 'h' => 4],
        'api_metrics' => ['x' => 6, 'y' => 6, 'w' => 6, 'h' => 4],
        'recent_orders' => ['x' => 0, 'y' => 10, 'w' => 12, 'h' => 4],
        'error_log' => ['x' => 0, 'y' => 14, 'w' => 6, 'h' => 4],
        'performance' => ['x' => 6, 'y' => 14, 'w' => 6, 'h' => 4],
    ];

    return get_option(self::LAYOUT_OPTION, $default_layout);
}

public function getAvailableWidgets(): array {
    return array_merge(parent::getAvailableWidgets(), [
        'sales_chart' => [
            'title' => 'Sales Overview',
            'description' => 'Display sales statistics over time',
            'refresh_interval' => 300,
            'type' => 'chart',
            'settings' => [
                'chart_type' => ['line'],
                'time_range' => ['day', 'week', 'month', 'year'],
                'show_legend' => true,
                'animate' => true,
            ],
        ]
    ]);
}