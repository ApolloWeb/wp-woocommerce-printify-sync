<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ImportInterface
{
    private string $currentTime = '2025-03-15 19:33:22';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addImportPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_validate_products', [$this, 'validateProducts']);
        add_action('wp_ajax_wpwps_import_products', [$this, 'handleImport']);
        add_action('wp_ajax_wpwps_check_import_status', [$this, 'checkStatus']);
        add_action('wp_ajax_wpwps_cancel_import', [$this, 'cancelImport']);
    }

    public function renderPage(): void
    {
        ?>
        <div class="printify-import-container">
            <div class="import-header">
                <h1>Printify Product Import</h1>
                <div class="import-tabs">
                    <button class="tab-button active" data-tab="import">Import</button>
                    <button class="tab-button" data-tab="history">History</button>
                    <button class="tab-button" data-tab="settings">Settings</button>
                </div>
            </div>

            <div class="import-tab-content" id="import">
                <div class="import-steps">
                    <div class="step active" id="step-1">
                        <h3>1. Select Products</h3>
                        <div class="input-group">
                            <input type="text" id="product-ids" placeholder="Enter product IDs (comma-separated)">
                            <button id="validate-products" class="secondary-button">Validate</button>
                        </div>
                        <div class="validation-results"></div>
                    </div>

                    <div class="step" id="step-2">
                        <h3>2. Configure Import</h3>
                        <div class="config-options">
                            <div class="option-group">
                                <label>Storage Options</label>
                                <div class="toggle-group">
                                    <label class="toggle">
                                        <input type="checkbox" id="use-r2" checked>
                                        <span class="slider"></span>
                                        R2 Storage
                                    </label>
                                    <label class="toggle">
                                        <input type="checkbox" id="optimize-images" checked>
                                        <span class="slider"></span>
                                        Optimize Images
                                    </label>
                                </div>
                            </div>

                            <div class="option-group">
                                <label>Import Options</label>
                                <div class="checkbox-group">
                                    <label>
                                        <input type="checkbox" id="skip-existing" checked>
                                        Skip existing products
                                    </label>
                                    <label>
                                        <input type="checkbox" id="draft-mode">
                                        Import as draft
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="step" id="step-3">
                        <h3>3. Import</h3>
                        <button id="start-import" class="primary-button" disabled>Start Import</button>
                        
                        <div id="import-progress" style="display: none;">
                            <div class="progress-details">
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                                <div class="progress-stats">
                                    <div class="stat-group">
                                        <span class="stat-label">Progress:</span>
                                        <span id="progress-percent">0%</span>
                                    </div>
                                    <div class="stat-group">
                                        <span class="stat-label">Products:</span>
                                        <span id="imported">0</span> / <span id="total">0</span>
                                    </div>
                                    <div class="stat-group">
                                        <span class="stat-label">Images:</span>
                                        <span id="images-processed">0</span> / <span id="total-images">0</span>
                                    </div>
                                    <div class="stat-group">
                                        <span class="stat-label">Errors:</span>
                                        <span id="error-count">0</span>
                                    </div>
                                </div>
                            </div>

                            <div class="progress-actions">
                                <button id="pause-import" class="secondary-button">Pause</button>
                                <button id="cancel-import" class="danger-button">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="import-log" class="collapsible">
                    <div class="log-header">
                        <h3>Import Log</h3>
                        <button class="toggle-log">Toggle</button>
                    </div>
                    <div class="log-content">
                        <div class="log-filters">
                            <button class="log-filter active" data-type="all">All</button>
                            <button class="log-filter" data-type="success">Success</button>
                            <button class="log-filter" data-type="error">Errors</button>
                            <button class="log-filter" data-type="warning">Warnings</button>
                        </div>
                        <div class="log-entries"></div>
                    </div>
                </div>
            </div>

            <div class="import-tab-content" id="history" style="display: none;">
                <div class="history-filters">
                    <input type="date" id="history-date" value="<?php echo date('Y-m-d'); ?>">
                    <select id="history-status">
                        <option value="">All Status</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button id="refresh-history" class="secondary-button">Refresh</button>
                </div>
                <div class="history-table"></div>
            </div>

            <div class="import-tab-content" id="settings" style="display: none;">
                <form id="import-settings">
                    <div class="settings-group">
                        <h3>Default Import Settings</h3>
                        <div class="setting-row">
                            <label>Default Product Status</label>
                            <select name="default_status">
                                <option value="publish">Published</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                        <div class="setting-row">
                            <label>Image Quality</label>
                            <input type="range" name="image_quality" min="60" max="100" value="82">
                            <span class="quality-value">82%</span>
                        </div>
                        <div class="setting-row">
                            <label>Max Concurrent Imports</label>
                            <input type="number" name="max_concurrent" min="1" max="10" value="3">
                        </div>
                    </div>
                    <div class="settings-group">
                        <h3>Notification Settings</h3>
                        <div class="setting-row">
                            <label>Email Notifications</label>
                            <input type="email" name="notification_email" value="<?php echo get_option('admin_email'); ?>">
                        </div>
                        <div class="setting-row">
                            <label>Notification Events</label>
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="notify_complete" checked>
                                    Import Complete
                                </label>
                                <label>
                                    <input type="checkbox" name="notify_errors" checked>
                                    Import Errors
                                </label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="primary-button">Save Settings</button>
                </form>
            </div>
        </div>
        <?php
    }
}