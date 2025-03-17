<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Settings\Sections;

class StorageSettingsSection extends AbstractSettingsSection
{
    protected function getSectionId(): string
    {
        return 'storage_settings';
    }

    protected function getSectionTitle(): string
    {
        return __('Storage Settings', 'wp-woocommerce-printify-sync');
    }

    public function getSettings(): array
    {
        return [
            [
                'id' => 'google_drive_section',
                'type' => 'section_start',
                'title' => __('Google Drive Integration', 'wp-woocommerce-printify-sync'),
                'collapsible' => true,
                'icon' => 'fab fa-google-drive'
            ],
            [
                'id' => 'google_drive_client_id',
                'type' => 'password',
                'title' => __('Client ID', 'wp-woocommerce-printify-sync'),
                'desc' => __('Enter your Google Drive API Client ID', 'wp-woocommerce-printify-sync'),
                'encrypted' => true
            ],
            [
                'id' => 'google_drive_client_secret',
                'type' => 'password',
                'title' => __('Client Secret', 'wp-woocommerce-printify-sync'),
                'desc' => __('Enter your Google Drive API Client Secret', 'wp-woocommerce-printify-sync'),
                'encrypted' => true
            ],
            [
                'id' => 'google_drive_refresh_token',
                'type' => 'password',
                'title' => __('Refresh Token', 'wp-woocommerce-printify-sync'),
                'desc' => __('Google Drive API Refresh Token', 'wp-woocommerce-printify-sync'),
                'encrypted' => true
            ],
            [
                'id' => 'google_drive_folder_id',
                'type' => 'text',
                'title' => __('Folder ID', 'wp-woocommerce-printify-sync'),
                'desc' => __('ID of the Google Drive folder for log storage', 'wp-woocommerce-printify-sync')
            ],
            [
                'type' => 'button',
                'title' => __('Test Google Drive Connection', 'wp-woocommerce-printify-sync'),
                'action' => 'test_google_drive',
                'class' => 'button-secondary',
                'icon' => 'fas fa-vial'
            ],
            [
                'type' => 'section_end'
            ],
            [
                'id' => 'cloudflare_r2_section',
                'type' => 'section_start',
                'title' => __('Cloudflare R2 Integration', 'wp-woocommerce-printify-sync'),
                'collapsible' => true,
                'icon' => 'fas fa-cloud'
            ],
            [
                'id' => 'r2_account_id',
                'type' => 'text',
                'title' => __('Account ID', 'wp-woocommerce-printify-sync'),
                'desc' => __('Your Cloudflare Account ID', 'wp-woocommerce-printify-sync')
            ],
            [
                'id' => 'r2_access_key_id',
                'type' => 'password',
                'title' => __('Access Key ID', 'wp-woocommerce-printify-sync'),
                'desc' => __('R2 Access Key ID', 'wp-woocommerce-printify-sync'),
                'encrypted' => true
            ],
            [
                'id' => 'r2_secret_access_key',
                'type' => 'password',
                'title' => __('Secret Access Key', 'wp-woocommerce-printify-sync'),
                'desc' => __('R2 Secret Access Key', 'wp-woocommerce-printify-sync'),
                'encrypted' => true
            ],
            [
                'id' => 'r2_bucket_name',
                'type' => 'text',
                'title' => __('Bucket Name', 'wp-woocommerce-printify-sync'),
                'desc' => __('R2 Bucket Name', 'wp-woocommerce-printify-sync')
            ],
            [
                'id' => 'r2_bucket_region',
                'type' => 'text',
                'title' => __('Bucket Region', 'wp-woocommerce-printify-sync'),
                'desc' => __('R2 Bucket Region', 'wp-woocommerce-printify-sync'),
                'default' => 'auto'
            ],
            [
                'type' => 'button',
                'title' => __('Test R2 Connection', 'wp-woocommerce-printify-sync'),
                'action' => 'test_r2_connection',
                'class' => 'button-secondary',
                'icon' => 'fas fa-vial'
            ],
            [
                'type' => 'section_end'
            ]
        ];
    }
}