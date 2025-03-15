<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AddonsPage
{
    private string $currentTime = '2025-03-15 18:50:47';
    private string $currentUser = 'ApolloWeb';

    public function render(): void
    {
        ?>
        <div class="wrap wpwps-wrapper">
            <h1><?php echo esc_html__('Premium Add-ons', 'wp-woocommerce-printify-sync'); ?></h1>

            <div class="wpwps-addons-grid">
                <?php foreach ($this->getAddons() as $slug => $addon): ?>
                    <div class="wpwps-addon-card">
                        <div class="wpwps-addon-header">
                            <h2><?php echo esc_html($addon['name']); ?></h2>
                            <span class="wpwps-addon-price">
                                $<?php echo number_format($addon['price'], 2); ?>
                            </span>
                        </div>

                        <div class="wpwps-addon-body">
                            <p><?php echo esc_html($addon['description']); ?></p>
                            
                            <ul class="wpwps-addon-features">
                                <?php foreach ($this->getAddonFeatures($slug) as $feature): ?>
                                    <li>
                                        <i class="material-icons">check_circle</i>
                                        <?php echo esc_html($feature); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div class="wpwps-addon-footer">
                            <?php if ($this->isAddonActive($slug)): ?>
                                <span class="wpwps-addon-status active">
                                    <?php echo esc_html__('Active', 'wp-woocommerce-printify-sync'); ?>
                                </span>
                            <?php else: ?>
                                <a href="<?php echo esc_url($addon['url']); ?>" 
                                   class="button button-primary" 
                                   target="_blank">
                                    <?php echo esc_html__('Get Add-on', 'wp-woocommerce-printify-sync'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function getAddonFeatures(string $slug): array
    {
        $features = [
            'r2_storage' => [
                'Automatic media offloading to R2',
                'CDN integration',
                'Image optimization',
                'Bulk transfer tool',
                'Advanced logging',
            ],
            'image_optimizer' => [
                'Automatic image optimization',
                'Multiple compression levels',
                'WebP conversion',
                'Bulk optimization',
                'Optimization statistics',
            ],
            'bulk_manager' => [
                'Bulk product import',
                'Scheduled syncs',
                'Advanced filtering',
                'Export capabilities',
                'Sync history',
            ]
        ];

        return $features[$slug] ?? [];
    }

    private function isAddonActive(string $slug): bool
    {
        return false; // Will be implemented in premium version
    }
}