class PrintifyDashboard {
    constructor() {
        this.grid = null;
        this.widgets = new Map();
        this.initialize();
    }

    initialize() {
        this.initializeGrid();
        this.initializeWidgets();
        this.bindEvents();
        this.setupRefreshIntervals();
    }

    initializeGrid() {
        this.grid = GridStack.init({
            column: 12,
            cellHeight: 60,
            animate: true,
            draggable: {
                handle: '.widget-header'
            },
            resizable: {
                handles: 'e,se,s,sw,w'
            }
        });

        this.grid.on('change', () => this.saveLayout());
    }

    initializeWidgets() {
        const widgets = wpwpsDashboard.widgets;
        const layout = wpwpsDashboard.layout;
        const settings = wpwpsDashboard.settings;

        Object.entries(widgets).forEach(([id, config]) => {
            const position = layout[id] || {};
            const widgetSettings = settings[id] || {};
            
            this.addWidget(id, config, position, widgetSettings);
        });
    }

    addWidget(id, config, position, settings) {
        const widget = new DashboardWidget(id, config, settings);
        this.widgets.set(id, widget);

        const element = widget.render();
        this.grid.addWidget(element, {
            x: position.x || 0,
            y: position.y || 0,
            w: position.w || 6,
            h: position.h || 4,
            minW: 3,
            maxW: 12,
            minH: 2,
            maxH: 8
        });
    }

    bindEvents() {
        // Handle widget settings changes
        $(document).on('wpwps_widget_settings_changed', (e, data) => {
            this.saveWidgetSettings(data.widgetId, data.settings);
        });

        // Handle widget refresh requests
        $(document).on('wpwps_widget_refresh', (e, data) => {
            this.refreshWidget(data.widgetId);
        });

        // Handle theme changes
        $(document).on('wpwps_theme_changed', (e, data) => {
            this.updateTheme(data.theme);
        });
    }

    setupRefreshIntervals() {
        this.widgets.forEach(widget => {
            const interval = widget.config.refresh_interval * 1000;
            if (interval > 0) {
                setInterval(() => this.refreshWidget(widget.id), interval);
            }
        });
    }

    saveLayout() {
        const layout = {};
        this.grid.engine.nodes.forEach(node => {
            layout[node.id] = {
                x: node.x,
                y: node.y,
                w: node.w,
                h: node.h
            };
        });

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'wpwps_save_dashboard_layout',
                nonce: wpwpsDashboard.nonce,
                layout: JSON.stringify(layout)
            }
        });
    }

    saveWidgetSettings(widgetId, settings) {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'wpwps_save_widget_settings',
                nonce: wpwpsDashboard.nonce,
                widget_id: widgetId,
                settings: JSON.stringify(settings)
            },
            success: () => this.refreshWidget(widgetId)
        });
    }

    refreshWidget(widgetId) {
        const widget = this.widgets.get(widgetId);
        if (widget) {
            widget.refresh();
        }
    }

    updateTheme(theme) {
        this.widgets.forEach(widget => widget.updateTheme(theme));
    }
}

class DashboardWidget {
    constructor(id, config, settings) {
        this.id = id;
        this.config = config;
        this.settings = settings;
        this.element = null;
        this.chart = null;
    }

    render() {
        this.element = $(`
            <div class="grid-stack-item" id="widget-${this.id}">
                <div class="grid-stack-item-content widget">
                    <div class="widget-header">
                        <h3>${this.config.title}</h3>
                        <div class="widget-actions">
                            <button class="widget-settings" title="Settings">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </button>
                            <button class="widget-refresh" title="Refresh">
                                <span class="dashicons dashicons-update"></span>
                            </button>
                        </div>
                    </div>
                    <div class="widget-content">
                        ${this.renderContent()}
                    </div>
                    <div class="widget-footer">
                        <span class="last-updated"></span>
                    </div>
                </div>
            </div>
        `);

        this.bindWidgetEvents();
        return this.element;
    }

    renderContent() {
        switch (this.config.type) {
            case 'chart':
                return this.renderChart();
            case 'grid':
                return this.renderGrid();
            case 'list':
                return this.renderList();
            default:
                return '<div class="widget-data"></div>';
        }
    }

    renderChart() {
        const chartType = this.settings.chart_type || this.config.settings.chart_type[0];
        return `<canvas class="widget-chart" data-type="${chartType}"></canvas>`;
    }

    renderGrid() {
        return `
            <div class="widget-grid">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            ${Object.values(this.config.settings.columns)
                                .map(column => `<th>${column}</th>`)
                                .join('')}
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="tablenav">
                    <div class="tablenav-pages"></div>
                </div>
            </div>
        `;
    }

    renderList() {
        return `
            <div class="widget-list">
                <ul class="list-items"></ul>
                <div class="list-footer">
                    <button class="load-more">Load More</button>
                </div>
            </div>
        `;
    }

    bindWidgetEvents() {
        const $widget = this.element;

        $widget.find('.widget-settings').on('click', () => this.showSettings());
        $widget.find('.widget-refresh').on('click', () => this.refresh());
        
        if (this.config.type === 'chart') {
            this.initializeChart();
        }
    }

    initializeChart() {
        const ctx = this.element.find('.widget-chart')[0];
        const chartType = this.settings.chart_type || this.config.settings.chart_type[0];

        this.chart = new Chart(ctx, {
            type: chartType,
            data: {
                labels: [],
                datasets: []
            },
            options: this.getChartOptions()
        });
    }

    getChartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: this.settings.animate !== false,
            plugins: {
                legend: {
                    display: this.settings.show_legend !== false,
                    position: 'bottom'
                }
            }
        };
    }

    showSettings() {
        const modal = new WidgetSettingsModal({
            title: `${this.config.title} Settings`,
            settings: this.settings,
            config