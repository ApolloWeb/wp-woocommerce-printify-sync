<div class="wpwps-widget-chart">
    <div class="wpwps-chart-header">
        <h3><?php _e('Sales Overview', 'wp-woocommerce-printify-sync'); ?></h3>
        <div class="wpwps-chart-actions">
            <select id="chart-period" class="wpwps-select">
                <option value="7"><?php _e('Last 7 Days', 'wp-woocommerce-printify-sync'); ?></option>
                <option value="30"><?php _e('Last 30 Days', 'wp-woocommerce-printify-sync'); ?></option>
                <option value="90"><?php _e('Last 90 Days', 'wp-woocommerce-printify-sync'); ?></option>
            </select>
        </div>
    </div>
    <div class="wpwps-chart-container">
        <canvas id="salesChart"></canvas>
    </div>
</div>
