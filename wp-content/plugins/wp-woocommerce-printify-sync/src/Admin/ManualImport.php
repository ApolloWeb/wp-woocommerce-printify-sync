<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ManualImport
{
    private string $currentTime = '2025-03-15 19:39:06';
    private string $currentUser = 'ApolloWeb';
    private ImportProcessor $processor;

    public function __construct()
    {
        $this->processor = new ImportProcessor();
        
        add_action('admin_menu', [$this, 'addImportPage']);
        add_action('admin_post_wpwps_manual_import', [$this, 'handleManualImport']);
    }

    public function addImportPage(): void
    {
        add_submenu_page(
            'tools.php',
            'Printify Manual Import',
            'Printify Import',
            'manage_options',
            'printify-import',
            [$this, 'renderPage']
        );
    }

    public function renderPage(): void
    {
        ?>
        <div class="wrap">
            <h1>Printify Manual Import</h1>
            <p class="description">Use this as a backup when webhook imports are not working.</p>

            <div class="card">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('wpwps_manual_import'); ?>
                    <input type="hidden" name="action" value="wpwps_manual_import">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="product_id">Product ID</label></th>
                            <td>
                                <input type="text" id="product_id" name="product_id" class="regular-text">
                            </td>
                        </tr>
                    </table>

                    <?php submit_button('Queue Import'); ?>
                </form>
            </div>

            <?php $this->renderQueueStatus(); ?>
        </div>
        <?php
    }

    private function renderQueueStatus(): void
    {
        global $wpdb;

        $stats = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM {$wpdb->prefix}wpwps_import_queue 
            GROUP BY status
        ");

        if (!empty($stats)): ?>
            <div class="card">
                <h2>Import Queue Status</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats as $stat): ?>
                            <tr>
                                <td><?php echo esc_html(ucfirst($stat->status)); ?></td>
                                <td><?php echo esc_html($stat->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif;
    }

    public function handleManualImport(): void
    {
        check_admin_referer('wpwps_manual_import');

        $productId = sanitize_text_field($_POST['product_id']);
        
        if (empty($productId)) {
            wp_redirect(add_query_arg('error', 'missing-id', wp_get_referer()));
            exit;
        }

        try {
            $this->processor->queueImport([
                'product_id' => $productId,
                'manual' => true,
                'triggered_by' => $this->currentUser
            ]);

            wp_redirect(add_query_arg('imported', '1', wp_get_referer()));
        } catch (\Exception $e) {
            wp_redirect(add_query_arg('error', 'import-failed', wp_get_referer()));
        }
        exit;
    }
}