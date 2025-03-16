<?php
/**
 * Admin Exchange Rates Template
 *
 * @var array $rates
 * @var array $history
 * @var string $baseCurrency
 * @var array $popularCurrencies
 * @var string $lastUpdate
 * @var array $stats
 */
?>

<div class="wrap wpwps-exchange-rates">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="fas fa-exchange-alt me-2"></i>
            <?php echo esc_html(get_admin_page_title()); ?>
        </h1>
        <div class="header-actions">
            <button class="wpwps-btn wpwps-btn-primary" id="updateRates">
                <i class="fas fa-sync-alt me-1"></i>
                <?php _e('Update Rates', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <?php foreach ($stats as $key => $stat): ?>
            <div class="col-md-6 col-xl-3">
                <div class="wpwps-card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-<?php echo esc_attr($stat['color']); ?>">
                                <i class="fas fa-<?php echo esc_attr($stat['icon']); ?>"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1"><?php echo esc_html($stat['label']); ?></h6>
                                <h3 class="mb-0">
                                    <?php echo esc_html($stat['value']); ?>
                                    <?php if (isset($stat['trend'])): ?>
                                        <small class="ms-2 fs-6 trend-<?php echo $stat['trend'] >= 0 ? 'up' : 'down'; ?>">
                                            <i class="fas fa-arrow-<?php echo $stat['trend'] >= 0 ? 'up' : 'down'; ?>"></i>
                                            <?php echo sprintf(__('%s%%', 'wp-woocommerce-printify-sync'), abs($stat['trend'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Popular Currencies -->
    <div class="row g-4 mb-4">
        <?php foreach ($popularCurrencies as $code => $currency): ?>
            <div class="col-md-6 col-xl-3">
                <div class="wpwps-card currency-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo esc_url(plugin_dir_url(WPWPS_PLUGIN_FILE) . "assets/images/flags/{$currency['flag']}.svg"); ?>" 
                                 class="currency-flag" 
                                 alt="<?php echo esc_attr($currency['name']); ?>">
                            <div class="ms-3">
                                <h6 class="mb-0"><?php echo esc_html($currency['name']); ?></h6>
                                <small class="text-muted"><?php echo esc_html($code); ?></small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="rate-value">
                                <?php echo esc_html($currency['symbol']); ?>
                                <span class="current-rate">
                                    <?php echo number_format($rates[$code] ?? 0, 4); ?>
                                </span>
                            </div>
                            <button class="btn btn-sm btn-outline-primary view-history" 
                                    data-currency="<?php echo esc_attr($code); ?>">
                                <i class="fas fa-chart-line"></i>
                            </button>
                        </div>
                        <?php if (isset($rates[$code . '_change'])): ?>
                            <div class="rate-change mt-2">
                                <span class="badge bg-<?php echo $rates[$code . '_change'] >= 0 ? 'success' : 'danger'; ?>">
                                    <i class="fas fa-arrow-<?php echo $rates[$code . '_change'] >= 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo sprintf(__('%s%%', 'wp-woocommerce-printify-sync'), abs($rates[$code . '_change'])); ?>
                                </span>
                                <small class="text-muted ms-2">
                                    <?php _e('24h change', 'wp-woocommerce-printify-sync'); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- All Rates Table -->
    <div class="wpwps-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php _e('All Exchange Rates', 'wp-woocommerce-printify-sync'); ?></h5>
            <small class="text-muted">
                <?php 
                printf(
                    __('Last updated: %s ago', 'wp-woocommerce-printify-sync'),
                    human_time_diff(strtotime($lastUpdate))
                ); 
                ?>
            </small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?php _e('Currency', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Rate', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('24h Change', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('7d Change', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('30d Change', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php _e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rates as $code => $rate): ?>
                        <?php if (is_numeric($rate)): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (isset($popularCurrencies[$code])): ?>
                                            <img src="<?php echo esc_url(plugin_dir_url(WPWPS_PLUGIN_FILE) . "assets/images/flags/{$popularCurrencies[$code]['flag']}.svg"); ?>" 
                                                 class="currency-flag-sm me-2" 
                                                 alt="">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo esc_html($code); ?></strong>
                                            <?php if (isset($popularCurrencies[$code])): ?>
                                                <small class="d-block text-muted">
                                                    <?php echo esc_html($popularCurrencies[$code]['name']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="rate-value">
                                        <?php echo number_format($rate, 4); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php $change24h = $rates[$code . '_24h_change'] ?? 0; ?>
                                    <span class="badge bg-<?php echo $change24h >= 0 ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-arrow-<?php echo $change24h >= 0 ? 'up' : 'down'; ?>"></i>
                                        <?php echo sprintf(__('%s%%', 'wp-woocommerce-printify-sync'), abs($change24h)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php $change7d = $rates[$code . '_7d_change'] ?? 0; ?>
                                    <span class="badge bg-<?php echo $change7d >= 0 ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-arrow-<?php echo $change7d >= 0 ? 'up' : 'down'; ?>"></i>
                                        <?php echo sprintf(__('%s%%', 'wp-woocommerce-printify-sync'), abs($change7d)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php $change30d = $rates[$code . '_30d_change'] ?? 0; ?>
                                    <span class="badge bg-<?php echo $change30d >= 0 ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-arrow-<?php echo $change30d >= 0 ? 'up' : 'down'; ?>"></i>
                                        <?php echo sprintf(__('%s%%', 'wp-woocommerce-printify-sync'), abs($change30d)); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary